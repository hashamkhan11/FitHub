<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Members;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipRenewalTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(Gym $gym): User
    {
        return User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
    }

    public function test_staff_can_renew_an_expired_members_membership_without_creating_a_new_member(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create(['price' => 50, 'duration_days' => 30]);
        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->create([
            'plan_id' => $plan->id,
            'end_date' => now()->subDay()->toDateString(),
            'payment_status' => 'paid',
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->assertSet('renew_start_date', now()->toDateString())
            ->set('renew_plan_id', $plan->id)
            ->call('renewMembership')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('members', 1);
        $this->assertDatabaseCount('memberships', 2);
        $this->assertDatabaseHas('memberships', [
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'payment_status' => 'pending',
            'price_paid' => 50,
        ]);
    }

    public function test_renewing_an_active_membership_defaults_the_start_date_to_the_day_after_it_ends(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create();
        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->create([
            'plan_id' => $plan->id,
            'end_date' => now()->addDays(5)->toDateString(),
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->assertSet('renew_start_date', now()->addDays(6)->toDateString());
    }

    public function test_renewal_sets_the_end_date_based_on_the_selected_plans_duration(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create(['duration_days' => 90]);
        $member = Member::factory()->for($gym)->create();
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->set('renew_plan_id', $plan->id)
            ->set('renew_start_date', now()->toDateString())
            ->call('renewMembership')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('memberships', [
            'member_id' => $member->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
        ]);
    }

    public function test_a_staff_member_cannot_renew_using_another_gyms_plan(): void
    {
        $gym = Gym::factory()->create();
        $otherGym = Gym::factory()->create();
        $foreignPlan = Plan::factory()->for($otherGym)->create();
        $member = Member::factory()->for($gym)->create();
        $this->actingAs($this->staffUser($gym));

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->set('renew_plan_id', $foreignPlan->id)
            ->set('renew_start_date', now()->toDateString())
            ->call('renewMembership');
    }

    public function test_a_staff_member_cannot_renew_another_gyms_member(): void
    {
        $gym = Gym::factory()->create();
        $otherGym = Gym::factory()->create();
        $member = Member::factory()->for($otherGym)->create();
        $this->actingAs($this->staffUser($gym));

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Members::class)->call('startRenewal', $member->id);
    }
}
