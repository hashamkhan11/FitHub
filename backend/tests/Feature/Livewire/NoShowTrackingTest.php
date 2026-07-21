<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Classes;
use App\Livewire\Insight;
use App\Models\Attendance;
use App\Models\Gym;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NoShowTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function owner(Gym $gym): User
    {
        return User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
    }

    public function test_a_booked_member_who_checked_in_that_day_is_not_a_no_show(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        $class = GymClass::factory()->for($gym)->create(['start_time' => now()->subDay()]);
        $class->book($member);
        Attendance::factory()->create([
            'gym_id' => $gym->id,
            'member_id' => $member->id,
            'checked_in_at' => $class->start_time,
        ]);
        $this->actingAs($this->owner($gym));

        Livewire::test(Classes::class)
            ->assertSee('0/1');
    }

    public function test_a_booked_member_with_no_matching_checkin_is_a_no_show(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        $class = GymClass::factory()->for($gym)->create(['start_time' => now()->subDay()]);
        $class->book($member);
        $this->actingAs($this->owner($gym));

        Livewire::test(Classes::class)
            ->assertSee('1/1');
    }

    public function test_an_upcoming_class_shows_a_dash_instead_of_a_no_show_count(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        $class = GymClass::factory()->for($gym)->create(['start_time' => now()->addDay()]);
        $class->book($member);
        $this->actingAs($this->owner($gym));

        Livewire::test(Classes::class)
            ->assertDontSee('0/1')
            ->assertDontSee('1/1');
    }

    public function test_insight_no_show_rate_reflects_recent_past_classes(): void
    {
        $gym = Gym::factory()->create();
        $attended = Member::factory()->for($gym)->create();
        $noShow = Member::factory()->for($gym)->create();
        $class = GymClass::factory()->for($gym)->create(['start_time' => now()->subDays(2)]);
        $class->book($attended);
        $class->book($noShow);
        Attendance::factory()->create([
            'gym_id' => $gym->id,
            'member_id' => $attended->id,
            'checked_in_at' => $class->start_time,
        ]);
        $this->actingAs($this->owner($gym));

        Livewire::test(Insight::class)
            ->assertSee('50%')
            ->assertSee('1 of 2 booked spots no-showed');
    }
}
