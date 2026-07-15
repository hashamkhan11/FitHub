<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Member extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'gym_id',
        'name',
        'email',
        'phone',
        'password',
        'photo_path',
        'join_date',
        'status',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'join_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            $member->qr_code ??= (string) Str::uuid();
        });
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
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
