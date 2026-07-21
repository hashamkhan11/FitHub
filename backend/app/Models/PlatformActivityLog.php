<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'platform_admin_id',
        'gym_id',
        'action',
        'description',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function platformAdmin(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /**
     * Record an action against the currently authenticated platform admin.
     */
    public static function record(string $action, string $description, ?Gym $gym = null): void
    {
        static::create([
            'platform_admin_id' => auth('platform')->id(),
            'gym_id' => $gym?->id,
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
