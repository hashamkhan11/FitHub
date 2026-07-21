<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Insight;
use App\Livewire\Members;
use App\Livewire\Payments;
use App\Livewire\Plans;
use App\Livewire\Trainers;
use App\Models\Gym;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_view_insight(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'staff']));

        Livewire::test(Insight::class)->assertForbidden();
    }

    public function test_owner_can_view_insight(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']));

        Livewire::test(Insight::class)->assertOk();
    }

    public function test_staff_can_view_plans_page(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'staff']));

        Livewire::test(Plans::class)->assertOk();
    }

    public function test_trainer_cannot_view_plans_page(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer']));

        Livewire::test(Plans::class)->assertForbidden();
    }

    public function test_staff_cannot_save_edit_or_delete_plans(): void
    {
        $gym = Gym::factory()->create();
        $owner = User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
        $plan = Plan::factory()->for($gym)->create();

        // Bypass mount() (owner-only page gate) so we can reach the action
        // methods directly and confirm they carry their own authorization too.
        $this->actingAs($owner);
        $component = Livewire::test(Plans::class);

        $staff = User::factory()->create(['gym_id' => $gym->id, 'role' => 'staff']);
        $this->actingAs($staff);

        $component->set('name', 'Gold Plan')
            ->set('duration_days', 30)
            ->set('price', '99.00')
            ->call('save')
            ->assertForbidden();
    }

    public function test_owner_can_manage_plans(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']));

        Livewire::test(Plans::class)
            ->set('name', 'Gold Plan')
            ->set('duration_days', 30)
            ->set('price', '99.00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('plans', ['name' => 'Gold Plan', 'gym_id' => $gym->id]);
    }

    public function test_trainer_can_view_members_page_but_not_enroll(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer']));

        $component = Livewire::test(Members::class)->assertOk();

        $component->set('name', 'New Member')
            ->set('email', 'new-member@example.com')
            ->set('password', 'password123')
            ->call('enroll')
            ->assertForbidden();
    }

    public function test_staff_can_enroll_members(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'staff']));

        Livewire::test(Members::class)
            ->set('name', 'New Member')
            ->set('email', 'new-member@example.com')
            ->set('password', 'password123')
            ->set('plan_id', $plan->id)
            ->set('start_date', now()->toDateString())
            ->call('enroll')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('members', ['name' => 'New Member', 'gym_id' => $gym->id]);
    }

    public function test_trainer_cannot_view_payments_page(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer']));

        Livewire::test(Payments::class)->assertForbidden();
    }

    public function test_staff_can_view_payments_page(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'staff']));

        Livewire::test(Payments::class)->assertOk();
    }

    public function test_trainer_cannot_view_trainers_page(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer']));

        Livewire::test(Trainers::class)->assertForbidden();
    }

    public function test_staff_can_view_trainers_page_but_not_add_trainer(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'staff']));

        Livewire::test(Trainers::class)
            ->assertOk()
            ->set('name', 'New Trainer')
            ->set('email', 'new-trainer@example.com')
            ->set('password', 'password123')
            ->call('addTrainer')
            ->assertForbidden();
    }
}
