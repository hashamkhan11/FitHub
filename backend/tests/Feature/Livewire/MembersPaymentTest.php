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

class MembersPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(Gym $gym): User
    {
        return User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
    }

    public function test_a_partial_payment_marks_the_membership_partial_and_keeps_a_balance(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create(['price' => 100]);
        $member = Member::factory()->for($gym)->create();
        $membership = Membership::factory()->for($member)->create([
            'plan_id' => $plan->id,
            'payment_status' => 'pending',
            'price_paid' => 100,
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startPayment', $membership->id)
            ->assertSet('payment_amount', '100.00')
            ->set('payment_amount', 40)
            ->set('payment_method', 'cash')
            ->call('recordPayment')
            ->assertHasNoErrors();

        $membership->refresh();

        $this->assertSame('partial', $membership->payment_status);
        $this->assertSame(40.0, (float) $membership->amount_paid);
        $this->assertSame(60.0, (float) $membership->balance_due);
        $this->assertDatabaseHas('payments', [
            'gym_id' => $gym->id,
            'membership_id' => $membership->id,
            'amount' => 40,
            'method' => 'cash',
        ]);
    }

    public function test_paying_the_remaining_balance_marks_the_membership_paid(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create(['price' => 100]);
        $member = Member::factory()->for($gym)->create();
        $membership = Membership::factory()->for($member)->create([
            'plan_id' => $plan->id,
            'payment_status' => 'pending',
            'price_paid' => 100,
        ]);
        $this->actingAs($this->staffUser($gym));

        $membership->payments()->create([
            'gym_id' => $gym->id,
            'amount' => 40,
            'method' => 'cash',
            'paid_at' => now(),
        ]);
        $membership->syncPaymentStatus();

        Livewire::test(Members::class)
            ->call('startPayment', $membership->id)
            ->assertSet('payment_amount', '60.00')
            ->call('recordPayment')
            ->assertHasNoErrors();

        $membership->refresh();

        $this->assertSame('paid', $membership->payment_status);
        $this->assertSame(0.0, (float) $membership->balance_due);
    }

    public function test_a_payment_exceeding_the_balance_due_is_rejected(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create(['price' => 100]);
        $member = Member::factory()->for($gym)->create();
        $membership = Membership::factory()->for($member)->create([
            'plan_id' => $plan->id,
            'payment_status' => 'pending',
            'price_paid' => 100,
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startPayment', $membership->id)
            ->set('payment_amount', 150)
            ->call('recordPayment')
            ->assertHasErrors('payment_amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_staff_member_cannot_record_a_payment_for_another_gyms_membership(): void
    {
        $gym = Gym::factory()->create();
        $otherGym = Gym::factory()->create();
        $plan = Plan::factory()->for($otherGym)->create(['price' => 100]);
        $member = Member::factory()->for($otherGym)->create();
        $membership = Membership::factory()->for($member)->create([
            'plan_id' => $plan->id,
            'payment_status' => 'pending',
            'price_paid' => 100,
        ]);
        $this->actingAs($this->staffUser($gym));

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Members::class)->call('startPayment', $membership->id);
    }
}
