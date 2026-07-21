<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_fetch_their_own_attendance_history(): void
    {
        $member = Member::factory()->create();

        Attendance::create([
            'gym_id' => $member->gym_id,
            'member_id' => $member->id,
            'checked_in_at' => now()->subDay(),
            'checked_out_at' => now()->subDay()->addHour(),
        ]);
        Attendance::create([
            'gym_id' => $member->gym_id,
            'member_id' => $member->id,
            'checked_in_at' => now(),
            'checked_out_at' => null,
        ]);

        Sanctum::actingAs($member, ['*']);

        $response = $this->getJson('/api/member/attendance');

        $response->assertOk();
        $this->assertCount(2, $response->json('attendance'));
        $this->assertNull($response->json('attendance.0.checked_out_at'));
    }

    public function test_member_only_sees_their_own_attendance(): void
    {
        $member = Member::factory()->create();
        $otherMember = Member::factory()->create();

        Attendance::create([
            'gym_id' => $member->gym_id,
            'member_id' => $member->id,
            'checked_in_at' => now(),
        ]);
        Attendance::create([
            'gym_id' => $otherMember->gym_id,
            'member_id' => $otherMember->id,
            'checked_in_at' => now(),
        ]);

        Sanctum::actingAs($member, ['*']);

        $response = $this->getJson('/api/member/attendance');

        $response->assertOk();
        $attendance = $response->json('attendance');

        $this->assertCount(1, $attendance);
        $this->assertSame($member->id, $attendance[0]['member_id']);
    }
}
