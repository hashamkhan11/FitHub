<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Attendance;
use App\Livewire\Members;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipFreezeTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(Gym $gym): User
    {
        return User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
    }

    public function test_freezing_an_active_membership_marks_it_paused(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        $membership = Membership::factory()->for($member)->create([
            'end_date' => now()->addDays(20)->toDateString(),
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startFreeze', $membership->id)
            ->call('freezeMembership')
            ->assertHasNoErrors();

        $membership->refresh();

        $this->assertTrue($membership->isPaused());
        $this->assertFalse($membership->isActive());
    }

    public function test_resuming_a_paused_membership_extends_the_end_date_by_the_frozen_duration(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        $membership = Membership::factory()->for($member)->create([
            'end_date' => now()->addDays(20)->toDateString(),
        ]);
        $membership->update(['paused_at' => now()->subDays(5)]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)->call('resumeMembership', $membership->id);

        $membership->refresh();

        $this->assertFalse($membership->isPaused());
        $this->assertSame(now()->addDays(25)->toDateString(), $membership->end_date->toDateString());
    }

    public function test_freezing_an_already_paused_membership_is_a_no_op(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        $originalPausedAt = now()->subDays(3);
        $membership = Membership::factory()->for($member)->create([
            'end_date' => now()->addDays(20)->toDateString(),
            'paused_at' => $originalPausedAt,
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startFreeze', $membership->id)
            ->call('freezeMembership')
            ->assertHasNoErrors();

        $membership->refresh();

        $this->assertSame($originalPausedAt->toDateTimeString(), $membership->paused_at->toDateTimeString());
    }

    public function test_a_paused_member_is_rejected_at_check_in(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->create([
            'end_date' => now()->addDays(20)->toDateString(),
            'paused_at' => now(),
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Attendance::class)
            ->call('checkIn', $member->qr_code)
            ->assertSet('lastSuccess', false);

        $this->assertDatabaseCount('attendances', 0);
    }
}
