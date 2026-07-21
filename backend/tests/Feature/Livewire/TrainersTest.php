<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Trainers;
use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainersTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(Gym $gym): User
    {
        return User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
    }

    public function test_it_adds_a_trainer_with_the_trainer_role(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Trainers::class)
            ->set('name', 'Coach Amir')
            ->set('email', 'amir@example.com')
            ->set('password', 'password123')
            ->call('addTrainer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'gym_id' => $gym->id,
            'name' => 'Coach Amir',
            'email' => 'amir@example.com',
            'role' => 'trainer',
        ]);
    }

    public function test_it_lists_only_trainers_from_the_current_gym(): void
    {
        $gym = Gym::factory()->create();
        $otherGym = Gym::factory()->create();
        User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer', 'name' => 'Local Trainer']);
        User::factory()->create(['gym_id' => $otherGym->id, 'role' => 'trainer', 'name' => 'Other Gym Trainer']);
        User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner', 'name' => 'Owner']);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Trainers::class)
            ->assertSee('Local Trainer')
            ->assertDontSee('Other Gym Trainer')
            ->assertDontSee('Owner');
    }

    public function test_it_updates_a_trainers_name_and_email(): void
    {
        $gym = Gym::factory()->create();
        $trainer = User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer']);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Trainers::class)
            ->call('startEdit', $trainer->id)
            ->set('edit_name', 'Updated Name')
            ->set('edit_email', 'updated@example.com')
            ->call('updateTrainer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $trainer->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_deleting_a_trainer_unassigns_their_members(): void
    {
        $gym = Gym::factory()->create();
        $trainer = User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer']);
        $member = Member::factory()->for($gym)->create(['trainer_id' => $trainer->id]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Trainers::class)->call('deleteTrainer', $trainer->id);

        $this->assertDatabaseMissing('users', ['id' => $trainer->id]);
        $this->assertNull($member->fresh()->trainer_id);
    }

    public function test_a_staff_member_cannot_manage_another_gyms_trainer(): void
    {
        $gym = Gym::factory()->create();
        $otherGym = Gym::factory()->create();
        $trainer = User::factory()->create(['gym_id' => $otherGym->id, 'role' => 'trainer']);
        $this->actingAs($this->staffUser($gym));

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Trainers::class)->call('startEdit', $trainer->id);
    }
}
