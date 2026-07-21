<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Member extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'gym_id',
        'trainer_id',
        'name',
        'email',
        'phone',
        'password',
        'photo_path',
        'join_date',
        'fcm_token',
        'reset_otp',
        'reset_otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reset_otp',
        'reset_otp_expires_at',
    ];

    protected $appends = ['display_code', 'photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'join_date' => 'date',
            'reset_otp_expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            $member->qr_code ??= (string) Str::uuid();

            if (empty($member->member_code)) {
                $member->member_code = (int) static::withTrashed()->where('gym_id', $member->gym_id)->max('member_code') + 1;
            }
        });
    }

    /**
     * Human-facing member ID, e.g. "M-0007" — distinguishes same-named members.
     */
    public function getDisplayCodeAttribute(): string
    {
        return 'M-'.str_pad((string) $this->member_code, 4, '0', STR_PAD_LEFT);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function hasActiveMembership(): bool
    {
        return $this->memberships->contains(fn (Membership $membership) => $membership->isActive());
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }
}
