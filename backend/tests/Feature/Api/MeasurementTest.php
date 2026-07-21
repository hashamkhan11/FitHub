<?php

namespace Tests\Feature\Api;

use App\Models\Measurement;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeasurementTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_log_a_measurement(): void
    {
        $member = Member::factory()->create();
        Sanctum::actingAs($member, ['*']);

        $response = $this->postJson('/api/member/measurements', [
            'recorded_at' => now()->toDateString(),
            'weight_kg' => 82.5,
            'body_fat_percentage' => 18.2,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('measurements', [
            'member_id' => $member->id,
            'gym_id' => $member->gym_id,
        ]);
    }

    public function test_member_only_sees_their_own_measurements(): void
    {
        $member = Member::factory()->create();
        $otherMember = Member::factory()->create();

        Measurement::factory()->for($member)->for($member->gym)->create();
        Measurement::factory()->for($otherMember)->for($otherMember->gym)->count(2)->create();

        Sanctum::actingAs($member, ['*']);

        $response = $this->getJson('/api/member/measurements');

        $response->assertOk();
        $measurements = $response->json('measurements');

        $this->assertCount(1, $measurements);
        $this->assertSame($member->id, $measurements[0]['member_id']);
    }
}
