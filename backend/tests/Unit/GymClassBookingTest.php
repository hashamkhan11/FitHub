<?php

namespace Tests\Unit;

use App\Models\GymClass;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymClassBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_confirms_a_seat_while_under_capacity(): void
    {
        $class = GymClass::factory()->create(['capacity' => 2]);
        $member = Member::factory()->for($class->gym)->create();

        $booking = $class->book($member);

        $this->assertSame('booked', $booking->status);
    }

    public function test_book_waitlists_once_capacity_is_reached(): void
    {
        $class = GymClass::factory()->create(['capacity' => 1]);
        $class->book(Member::factory()->for($class->gym)->create());

        $booking = $class->book(Member::factory()->for($class->gym)->create());

        $this->assertSame('waitlisted', $booking->status);
    }

    public function test_book_rejects_a_second_booking_from_the_same_member(): void
    {
        $class = GymClass::factory()->create(['capacity' => 5]);
        $member = Member::factory()->for($class->gym)->create();
        $class->book($member);

        $this->expectException(\DomainException::class);

        $class->book($member);
    }

    public function test_cancel_booking_promotes_the_oldest_waitlisted_booking(): void
    {
        $class = GymClass::factory()->create(['capacity' => 1]);

        $booked = $class->book(Member::factory()->for($class->gym)->create());
        $firstWaitlisted = $class->book(Member::factory()->for($class->gym)->create());
        $secondWaitlisted = $class->book(Member::factory()->for($class->gym)->create());

        $promoted = $class->cancelBooking($booked);

        $this->assertSame($firstWaitlisted->id, $promoted->id);
        $this->assertSame('booked', $firstWaitlisted->fresh()->status);
        $this->assertSame('waitlisted', $secondWaitlisted->fresh()->status);
        $this->assertSame('cancelled', $booked->fresh()->status);
    }

    public function test_cancel_booking_returns_null_when_a_waitlisted_booking_is_cancelled(): void
    {
        $class = GymClass::factory()->create(['capacity' => 1]);
        $class->book(Member::factory()->for($class->gym)->create());
        $waitlisted = $class->book(Member::factory()->for($class->gym)->create());

        $promoted = $class->cancelBooking($waitlisted);

        $this->assertNull($promoted);
        $this->assertSame('cancelled', $waitlisted->fresh()->status);
    }
}
