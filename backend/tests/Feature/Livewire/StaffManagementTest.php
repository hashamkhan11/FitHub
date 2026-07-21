<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Staff;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private function owner(Gym $gym): User
    {
        return User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
    }

    public function test_staff_cannot_load_the_staff_page(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'staff']));

        Livewire::test(Staff::class)->assertForbidden();
    }

    public function test_owner_can_add_a_staff_account(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs($this->owner($gym));

        Livewire::test(Staff::class)
            ->set('name', 'Front Desk Amina')
            ->set('email', 'amina@example.com')
            ->set('password', 'password123')
            ->call('addStaff')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'gym_id' => $gym->id,
            'name' => 'Front Desk Amina',
            'email' => 'amina@example.com',
            'role' => 'staff',
        ]);
    }

    public function test_it_lists_only_staff_from_the_current_gym(): void
    {
        $gym = Gym::factory()->create();
        $otherGym = Gym::factory()->create();
        User::factory()->create(['gym_id' => $gym->id, 'role' => 'staff', 'name' => 'Local Staff']);
        User::factory()->create(['gym_id' => $otherGym->id, 'role' => 'staff', 'name' => 'Other Gym Staff']);
        $owner = $this->owner($gym);
        $this->actingAs($owner);

        Livewire::test(Staff::class)
            ->assertSee('Local Staff')
            ->assertDontSee('Other Gym Staff')
            ->assertDontSee($owner->name);
    }

    public function test_owner_can_update_a_staff_accounts_name_and_email(): void
    {
        $gym = Gym::factory()->create();
        $staff = User::factory()->create(['gym_id' => $gym->id, 'role' => 'staff']);
        $this->actingAs($this->owner($gym));

        Livewire::test(Staff::class)
            ->call('startEdit', $staff->id)
            ->set('edit_name', 'Updated Name')
            ->set('edit_email', 'updated@example.com')
            ->call('updateStaff')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_owner_can_delete_a_staff_account(): void
    {
        $gym = Gym::factory()->create();
        $staff = User::factory()->create(['gym_id' => $gym->id, 'role' => 'staff']);
        $this->actingAs($this->owner($gym));

        Livewire::test(Staff::class)->call('deleteStaff', $staff->id);

        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_an_owner_account_cannot_be_deleted_through_the_staff_page(): void
    {
        $gym = Gym::factory()->create();
        $owner = $this->owner($gym);
        $anotherOwner = $this->owner($gym);
        $this->actingAs($owner);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Staff::class)->call('deleteStaff', $anotherOwner->id);

        $this->assertDatabaseHas('users', ['id' => $anotherOwner->id]);
    }

    public function test_an_owner_cannot_manage_another_gyms_staff(): void
    {
        $gym = Gym::factory()->create();
        $otherGym = Gym::factory()->create();
        $staff = User::factory()->create(['gym_id' => $otherGym->id, 'role' => 'staff']);
        $this->actingAs($this->owner($gym));

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Staff::class)->call('startEdit', $staff->id);
    }
}
