<?php

namespace Tests\Feature\Api;

use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MembershipTrainerTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_endpoint_includes_the_assigned_trainer(): void
    {
        $gym = Gym::factory()->create();
        $trainer = User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer', 'name' => 'Coach Amir']);
        $member = Member::factory()->for($gym)->create(['trainer_id' => $trainer->id]);
        Sanctum::actingAs($member, ['*']);

        $response = $this->getJson('/api/member/membership');

        $response->assertOk()->assertJson([
            'trainer' => ['name' => 'Coach Amir'],
        ]);
    }

    public function test_membership_endpoint_returns_null_trainer_when_unassigned(): void
    {
        $member = Member::factory()->create(['trainer_id' => null]);
        Sanctum::actingAs($member, ['*']);

        $response = $this->getJson('/api/member/membership');

        $response->assertOk()->assertJson(['trainer' => null]);
    }
}
