<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Attendance extends Model
{
    use BelongsToGym, HasFactory;

    protected $fillable = [
        'gym_id',
        'member_id',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Shared check-in/check-out toggle used by both the QR-scan dashboard flow
     * and device-driven entry points (e.g. the fingerprint scanner).
     *
     * @return array{success: bool, unlock: bool, message: string}
     */
    public static function recordScan(Member $member): array
    {
        return DB::transaction(function () use ($member) {
            // Lock the member row for the duration of this transaction so two
            // near-simultaneous scans (double-tap, a flaky sensor, two devices) can't
            // both read "no open session" and both insert a fresh check-in.
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();

            // Not restricted to today: a forgotten checkout from a previous day must
            // still be found and closed here, or it stays open forever and every
            // later scan creates a brand-new check-in on top of it.
            $openAttendance = $member->attendances()
                ->whereNull('checked_out_at')
                ->latest('checked_in_at')
                ->first();

            if ($openAttendance) {
                $openAttendance->update(['checked_out_at' => now()]);

                return [
                    'success' => true,
                    'unlock' => true,
                    'message' => "Checked out: {$member->name}",
                ];
            }

            if (! $member->activeMembership()) {
                return [
                    'success' => false,
                    'unlock' => false,
                    'message' => "{$member->name} has no active membership.",
                ];
            }

            $member->attendances()->create([
                'gym_id' => $member->gym_id,
                'checked_in_at' => now(),
            ]);

            return [
                'success' => true,
                'unlock' => true,
                'message' => "Checked in: {$member->name}",
            ];
        });
    }
}
