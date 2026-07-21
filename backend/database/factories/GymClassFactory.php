<?php

namespace Database\Factories;

use App\Models\Gym;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\GymClass>
 */
class GymClassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'name' => fake()->randomElement(['Spin', 'Yoga', 'HIIT', 'Pilates']),
            'instructor_name' => fake()->name(),
            'start_time' => now()->addDay(),
            'duration_minutes' => 60,
            'capacity' => 2,
            'is_active' => true,
        ];
    }
}
