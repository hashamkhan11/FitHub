<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\LockDevice;
use App\Models\Member;
use Illuminate\Http\Request;

class FingerprintController extends Controller
{
    /**
     * Called by the ESP32 the moment its fingerprint sensor reports a match
     * (matching itself happens on-sensor — only the numeric template ID
     * reaches us). Authenticated the same way as /lock/poll: a per-device
     * token in the X-Device-Token header, no user session.
     */
    public function scan(Request $request)
    {
        $device = LockDevice::findByToken((string) $request->header('X-Device-Token'));

        abort_unless($device !== null, 401);

        $validated = $request->validate([
            'fingerprint_id' => ['required', 'integer'],
        ]);

        $member = Member::where('gym_id', $device->gym_id)
            ->where('fingerprint_id', $validated['fingerprint_id'])
            ->first();

        if (! $member) {
            return response()->json([
                'unlock' => false,
                'message' => 'Fingerprint not recognized.',
            ]);
        }

        $result = Attendance::recordScan($member);

        ActivityLog::create([
            'gym_id' => $device->gym_id,
            'user_id' => null,
            'action' => 'fingerprint.scan',
            'description' => "{$member->name} scanned in via fingerprint at {$device->name}.",
            'created_at' => now(),
        ]);

        return response()->json([
            'unlock' => $result['unlock'],
            'message' => $result['message'],
        ]);
    }
}
