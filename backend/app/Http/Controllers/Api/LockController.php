<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LockCommand;
use App\Models\LockDevice;
use App\Models\Member;
use Illuminate\Http\Request;

class LockController extends Controller
{
    /**
     * Devices a member is allowed to see/unlock — every lock at their own gym.
     */
    public function devices(Request $request)
    {
        $devices = LockDevice::where('gym_id', $request->user()->gym_id)
            ->orderBy('name')
            ->get(['id', 'name', 'last_seen_at']);

        return response()->json(['devices' => $devices]);
    }

    public function unlock(Request $request, LockDevice $device)
    {
        abort_unless($device->gym_id === $request->user()->gym_id, 404);

        if (! $request->user()->hasActiveMembership()) {
            return response()->json(['message' => 'Your membership is not active.'], 403);
        }

        $command = $device->commands()->create([
            'action' => 'open',
            'status' => 'pending',
            'requester_type' => Member::class,
            'requester_id' => $request->user()->id,
        ]);

        ActivityLog::create([
            'gym_id' => $device->gym_id,
            'user_id' => null,
            'action' => 'lock.unlock_requested',
            'description' => "{$request->user()->name} unlocked {$device->name} via the mobile app.",
            'created_at' => now(),
        ]);

        return response()->json(['command_id' => $command->id, 'status' => $command->status]);
    }

    /**
     * Polled by the ESP32 every few seconds. Authenticated by a per-device
     * token (not Sanctum) sent in the X-Device-Token header — the device has
     * no user session, just a long-lived secret issued when it was registered.
     */
    public function pollCommands(Request $request)
    {
        $device = LockDevice::findByToken((string) $request->header('X-Device-Token'));

        abort_unless($device !== null, 401);

        $device->update(['last_seen_at' => now()]);

        $commands = $device->commands()
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get(['id', 'action', 'created_at']);

        return response()->json(['commands' => $commands]);
    }

    public function ackCommand(Request $request, LockCommand $command)
    {
        $device = LockDevice::findByToken((string) $request->header('X-Device-Token'));

        abort_unless($device !== null && $command->lock_device_id === $device->id, 401);

        $validated = $request->validate([
            'status' => ['required', 'in:completed,failed'],
        ]);

        $validated['status'] === 'completed' ? $command->markCompleted() : $command->markFailed();

        return response()->json(['message' => 'Acknowledged.']);
    }
}
