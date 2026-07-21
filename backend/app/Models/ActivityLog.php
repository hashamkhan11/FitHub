<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'gym_id',
        'user_id',
        'action',
        'description',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an action against the currently authenticated staff/owner user.
     */
    public static function record(string $action, string $description): void
    {
        static::create([
            'gym_id' => auth()->user()?->gym_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
