<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Members;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MembersTrainerAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(Gym $gym): User
    {
        return User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
    }

    public function test_enrolling_a_member_can_assign_a_trainer_from_the_same_gym(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create();
        $trainer = User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer']);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->set('name', 'New Member')
            ->set('email', 'newmember@example.com')
            ->set('phone', '555-0100')
            ->set('password', 'password123')
            ->set('plan_id', $plan->id)
            ->set('trainer_id', $trainer->id)
            ->set('start_date', now()->toDateString())
            ->call('enroll')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('members', [
            'email' => 'newmember@example.com',
            'trainer_id' => $trainer->id,
        ]);
    }

    public function test_a_trainer_from_another_gym_cannot_be_assigned_during_enrollment(): void
    {
        $gym = Gym::factory()->create();
        $otherGym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create();
        $foreignTrainer = User::factory()->create(['gym_id' => $otherGym->id, 'role' => 'trainer']);
        $this->actingAs($this->staffUser($gym));

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Members::class)
            ->set('name', 'New Member')
            ->set('email', 'newmember@example.com')
            ->set('phone', '555-0100')
            ->set('password', 'password123')
            ->set('plan_id', $plan->id)
            ->set('trainer_id', $foreignTrainer->id)
            ->set('start_date', now()->toDateString())
            ->call('enroll');

        $this->assertDatabaseMissing('members', ['email' => 'newmember@example.com']);
    }

    public function test_editing_a_member_can_reassign_their_trainer(): void
    {
        $gym = Gym::factory()->create();
        $trainerOne = User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer']);
        $trainerTwo = User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer']);
        $member = Member::factory()->for($gym)->create(['trainer_id' => $trainerOne->id]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startEdit', $member->id)
            ->assertSet('edit_trainer_id', $trainerOne->id)
            ->set('edit_trainer_id', $trainerTwo->id)
            ->call('updateMember')
            ->assertHasNoErrors();

        $this->assertSame($trainerTwo->id, $member->fresh()->trainer_id);
    }

    public function test_editing_a_member_can_unassign_their_trainer(): void
    {
        $gym = Gym::factory()->create();
        $trainer = User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer']);
        $member = Member::factory()->for($gym)->create(['trainer_id' => $trainer->id]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startEdit', $member->id)
            ->set('edit_trainer_id', null)
            ->call('updateMember')
            ->assertHasNoErrors();

        $this->assertNull($member->fresh()->trainer_id);
    }
}
