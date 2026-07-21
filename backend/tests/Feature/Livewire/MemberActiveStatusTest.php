<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Members;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberActiveStatusTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(Gym $gym): User
    {
        return User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
    }

    public function test_a_member_with_an_expired_membership_shows_as_inactive(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->create([
            'end_date' => now()->subMonths(6)->toDateString(),
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->assertSee('Inactive')
            ->assertDontSee('Active');
    }

    public function test_a_member_with_a_current_membership_shows_as_active(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->create([
            'end_date' => now()->addDays(10)->toDateString(),
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->assertSee('Active')
            ->assertDontSee('Inactive');
    }

    public function test_renewing_an_expired_membership_flips_the_member_back_to_active(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create();
        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->create([
            'plan_id' => $plan->id,
            'end_date' => now()->subMonths(6)->toDateString(),
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->assertSee('Inactive')
            ->call('startRenewal', $member->id)
            ->set('renew_plan_id', $plan->id)
            ->set('renew_start_date', now()->toDateString())
            ->call('renewMembership')
            ->assertSee('Active')
            ->assertDontSee('Inactive');
    }
}
