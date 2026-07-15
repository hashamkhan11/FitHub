<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GymClass extends Model
{
    protected $fillable = [
        'gym_id',
        'name',
        'instructor_name',
        'start_time',
        'duration_minutes',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function book(Member $member): Booking
    {
        $alreadyActive = $this->bookings()
            ->where('member_id', $member->id)
            ->whereIn('status', ['booked', 'waitlisted'])
            ->exists();

        if ($alreadyActive) {
            throw new \DomainException('This member already has a booking for this class.');
        }

        $bookedCount = $this->bookings()->where('status', 'booked')->count();
        $status = $bookedCount < $this->capacity ? 'booked' : 'waitlisted';

        return $this->bookings()->create([
            'member_id' => $member->id,
            'status' => $status,
        ]);
    }

    public function cancelBooking(Booking $booking): void
    {
        $wasBooked = $booking->status === 'booked';

        $booking->update(['status' => 'cancelled']);

        if (! $wasBooked) {
            return;
        }

        $this->bookings()->where('status', 'waitlisted')->oldest()->first()?->update(['status' => 'booked']);
    }
}
