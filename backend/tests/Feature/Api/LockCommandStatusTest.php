<?php

namespace Tests\Feature\Api;

use App\Models\Gym;
use App\Models\LockDevice;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LockCommandStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createCommand(Gym $gym, Member $requester): \App\Models\LockCommand
    {
        [$device] = LockDevice::issueToken($gym->id, 'Front Door');

        return $device->commands()->create([
            'action' => 'open',
            'status' => 'pending',
            'requester_type' => Member::class,
            'requester_id' => $requester->id,
        ]);
    }

    public function test_member_can_poll_the_status_of_their_own_gyms_command(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        $command = $this->createCommand($gym, $member);

        Sanctum::actingAs($member, ['*']);

        $response = $this->getJson("/api/lock/commands/{$command->id}/status");

        $response->assertOk();
        $this->assertSame('pending', $response->json('status'));
    }

    public function test_member_sees_the_updated_status_once_the_device_acknowledges(): void
    {
        $gym = Gym::factory()->create();
        $member = Member::factory()->for($gym)->create();
        $command = $this->createCommand($gym, $member);
        $command->markCompleted();

        Sanctum::actingAs($member, ['*']);

        $response = $this->getJson("/api/lock/commands/{$command->id}/status");

        $response->assertOk();
        $this->assertSame('completed', $response->json('status'));
    }

    public function test_member_cannot_poll_a_commands_status_from_another_gym(): void
    {
        $gym = Gym::factory()->create();
        $owner = Member::factory()->for($gym)->create();
        $command = $this->createCommand($gym, $owner);

        $otherMember = Member::factory()->create();
        Sanctum::actingAs($otherMember, ['*']);

        $response = $this->getJson("/api/lock/commands/{$command->id}/status");

        $response->assertNotFound();
    }
}
