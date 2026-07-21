<?php

namespace Database\Factories;

use App\Models\GymClass;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gym_class_id' => GymClass::factory(),
            'member_id' => Member::factory(),
            'status' => 'booked',
            'reminder_sent_at' => null,
        ];
    }
}
