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
        return $this->sendCommand($request, $device, 'open', 'unlocked');
    }

    public function lock(Request $request, LockDevice $device)
    {
        return $this->sendCommand($request, $device, 'close', 'locked');
    }

    private function sendCommand(Request $request, LockDevice $device, string $action, string $verb)
    {
        abort_unless($device->gym_id === $request->user()->gym_id, 404);

        if (! $request->user()->hasActiveMembership()) {
            return response()->json(['message' => 'Your membership is not active.'], 403);
        }

        $command = $device->commands()->create([
            'action' => $action,
            'status' => 'pending',
            'requester_type' => Member::class,
            'requester_id' => $request->user()->id,
            'expires_at' => now()->addMinutes(2),
        ]);

        ActivityLog::create([
            'gym_id' => $device->gym_id,
            'user_id' => null,
            'action' => "lock.{$action}_requested",
            'description' => "{$request->user()->name} {$verb} {$device->name} via the mobile app.",
            'created_at' => now(),
        ]);

        return response()->json(['command_id' => $command->id, 'status' => $command->status]);
    }

    /**
     * Polled by the mobile app after sending a command, so it can show
     * "unlocked"/"failed" as soon as the device acknowledges instead of
     * guessing with a fixed delay.
     */
    public function commandStatus(Request $request, LockCommand $command)
    {
        abort_unless($command->device->gym_id === $request->user()->gym_id, 404);

        return response()->json(['status' => $command->status]);
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

        // A device that reconnects after being offline should never execute
        // commands queued while it was gone — expire anything stale before
        // handing back what's left.
        $device->commands()
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired', 'completed_at' => now()]);

        $commands = $device->commands()
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at')
            ->get(['id', 'action', 'payload', 'created_at']);

        return response()->json(['commands' => $commands]);
    }

    public function ackCommand(Request $request, int $command)
    {
        $device = LockDevice::findByToken((string) $request->header('X-Device-Token'));

        // Resolve the device token first so an invalid/missing token always
        // gets a 401, regardless of whether the command ID exists — otherwise
        // an unauthenticated caller could tell real IDs apart from fake ones.
        abort_unless($device !== null, 401);

        $command = $device->commands()->find($command);

        abort_unless($command !== null, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:completed,failed,in_progress'],
        ]);

        match ($validated['status']) {
            'completed' => $command->markCompleted(),
            'failed' => $command->markFailed(),
            'in_progress' => $command->markInProgress(),
        };

        return response()->json(['message' => 'Acknowledged.']);
    }

    /**
     * Lets the ESP32 post a human-readable interim status (e.g. "Place finger
     * again") while a multi-step command like fingerprint enrollment is still
     * running, so the dashboard can show live progress instead of just a spinner.
     */
    public function progressCommand(Request $request, int $command)
    {
        $device = LockDevice::findByToken((string) $request->header('X-Device-Token'));

        abort_unless($device !== null, 401);

        $command = $device->commands()->find($command);

        abort_unless($command !== null, 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:255'],
        ]);

        $command->update(['progress_message' => $validated['message']]);

        return response()->json(['message' => 'ok']);
    }
}
