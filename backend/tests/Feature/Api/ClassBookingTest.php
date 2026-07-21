<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\GymClass;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\FakesFirebase;
use Tests\TestCase;

class ClassBookingTest extends TestCase
{
    use FakesFirebase, RefreshDatabase;

    public function test_booking_within_capacity_is_confirmed_and_notifies_the_member(): void
    {
        $messaging = $this->fakeFirebaseMessaging();

        $class = GymClass::factory()->create(['capacity' => 2]);
        $member = Member::factory()->for($class->gym)->create(['fcm_token' => 'token-123']);
        Sanctum::actingAs($member, ['*']);

        $response = $this->postJson("/api/classes/{$class->id}/book");

        $response->assertOk();
        $this->assertSame('booked', $response->json('booking.status'));
        $this->assertDatabaseHas('bookings', [
            'gym_class_id' => $class->id,
            'member_id' => $member->id,
            'status' => 'booked',
        ]);
        $messaging->shouldHaveReceived('send')->once();
    }

    public function test_booking_beyond_capacity_waitlists_the_member(): void
    {
        $this->fakeFirebaseMessaging();

        $class = GymClass::factory()->create(['capacity' => 1]);
        Booking::factory()->for($class, 'gymClass')->create(['status' => 'booked']);

        $member = Member::factory()->for($class->gym)->create();
        Sanctum::actingAs($member, ['*']);

        $response = $this->postJson("/api/classes/{$class->id}/book");

        $response->assertOk();
        $this->assertSame('waitlisted', $response->json('booking.status'));
    }

    public function test_member_cannot_double_book_the_same_class(): void
    {
        $this->fakeFirebaseMessaging();

        $class = GymClass::factory()->create(['capacity' => 5]);
        $member = Member::factory()->for($class->gym)->create();
        Booking::factory()->for($class, 'gymClass')->for($member)->create(['status' => 'booked']);

        Sanctum::actingAs($member, ['*']);

        $response = $this->postJson("/api/classes/{$class->id}/book");

        $response->assertStatus(422);
    }

    public function test_cancelling_a_booking_promotes_the_oldest_waitlisted_member_and_notifies_them(): void
    {
        $messaging = $this->fakeFirebaseMessaging();

        $class = GymClass::factory()->create(['capacity' => 1]);

        $bookedMember = Member::factory()->for($class->gym)->create();
        $booking = Booking::factory()->for($class, 'gymClass')->for($bookedMember)->create(['status' => 'booked']);

        $waitlistedMember = Member::factory()->for($class->gym)->create(['fcm_token' => 'waitlisted-token']);
        $waitlistedBooking = Booking::factory()->for($class, 'gymClass')->for($waitlistedMember)->create(['status' => 'waitlisted']);

        Sanctum::actingAs($bookedMember, ['*']);

        $this->postJson("/api/bookings/{$booking->id}/cancel")->assertOk();

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('booked', $waitlistedBooking->fresh()->status);
        $messaging->shouldHaveReceived('send')->once();
    }
}
