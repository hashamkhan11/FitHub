<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LockCommand extends Model
{
    protected $fillable = [
        'lock_device_id',
        'action',
        'payload',
        'status',
        'progress_message',
        'requester_type',
        'requester_id',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(LockDevice::class, 'lock_device_id');
    }

    public function requester(): MorphTo
    {
        return $this->morphTo();
    }

    public function markInProgress(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed', 'completed_at' => now()]);
        $this->applyFingerprintEffect(true);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed', 'completed_at' => now()]);
        $this->applyFingerprintEffect(false);
    }

    public function markExpired(): void
    {
        $this->update(['status' => 'expired', 'completed_at' => now()]);
    }

    /**
     * "enroll"/"delete_fingerprint" commands carry a member_id + fingerprint_id
     * in their payload. On success, sync that back onto the Member here so the
     * dashboard reflects it as soon as the device acknowledges - the device
     * itself only ever sees the numeric fingerprint ID, never the member.
     */
    private function applyFingerprintEffect(bool $succeeded): void
    {
        if (! $succeeded || ! in_array($this->action, ['enroll', 'delete_fingerprint'], true)) {
            return;
        }

        $memberId = $this->payload['member_id'] ?? null;

        if (! $memberId) {
            return;
        }

        $member = Member::find($memberId);

        if (! $member) {
            return;
        }

        if ($this->action === 'enroll') {
            $member->update([
                'fingerprint_id' => $this->payload['fingerprint_id'] ?? null,
                'fingerprint_device_id' => $this->lock_device_id,
            ]);
        } else {
            $member->update(['fingerprint_id' => null, 'fingerprint_device_id' => null]);
        }
    }
}
