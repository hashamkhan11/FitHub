<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Attendance;
use App\Models\Attendance as AttendanceModel;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(Gym $gym): User
    {
        return User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
    }

    public function test_scanning_an_unknown_qr_code_is_rejected(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Attendance::class)
            ->call('checkIn', 'not-a-real-code')
            ->assertSet('lastSuccess', false)
            ->assertSet('lastMessage', 'QR code not recognized.');
    }

    public function test_member_without_an_active_membership_is_rejected(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Attendance::class)
            ->call('checkIn', $member->qr_code)
            ->assertSet('lastSuccess', false);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_member_with_active_membership_is_checked_in(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->create();
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Attendance::class)
            ->call('checkIn', $member->qr_code)
            ->assertSet('lastSuccess', true);

        $this->assertDatabaseHas('attendances', [
            'gym_id' => $gym->id,
            'member_id' => $member->id,
            'checked_out_at' => null,
        ]);
    }

    public function test_scanning_again_the_same_day_checks_the_member_out(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->create();
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Attendance::class)->call('checkIn', $member->qr_code);

        $this->assertDatabaseCount('attendances', 1);

        Livewire::test(Attendance::class)
            ->call('checkIn', $member->qr_code)
            ->assertSet('lastSuccess', true);

        $this->assertDatabaseCount('attendances', 1);
        $this->assertNotNull(AttendanceModel::first()->checked_out_at);
    }
}
