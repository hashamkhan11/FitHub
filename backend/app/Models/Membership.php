<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    protected $fillable = [
        'member_id',
        'plan_id',
        'start_date',
        'end_date',
        'payment_status',
        'price_paid',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price_paid' => 'decimal:2',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return $this->end_date?->isFuture() ?? false;
    }
}
