<?php

namespace Tests\Feature\Api;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_login_with_correct_credentials(): void
    {
        $member = Member::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'member']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertSame($member->id, $response->json('member.id'));
    }

    public function test_login_fails_with_incorrect_password(): void
    {
        Member::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        Member::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $token = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ])->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_deleted_token_can_no_longer_authenticate(): void
    {
        $member = Member::factory()->create();
        $token = $member->createToken('mobile-app')->plainTextToken;

        $member->tokens()->delete();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/member')
            ->assertUnauthorized();
    }
}
