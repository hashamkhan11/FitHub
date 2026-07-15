<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\GymClass;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $classes = GymClass::where('gym_id', $request->user()->gym_id)
            ->where('is_active', true)
            ->where('start_time', '>=', now())
            ->withCount(['bookings as booked_count' => fn ($query) => $query->where('status', 'booked')])
            ->orderBy('start_time')
            ->get();

        $myBookings = Booking::where('member_id', $request->user()->id)
            ->whereIn('gym_class_id', $classes->pluck('id'))
            ->where('status', '!=', 'cancelled')
            ->get()
            ->keyBy('gym_class_id');

        $classes->each(function ($class) use ($myBookings) {
            $booking = $myBookings->get($class->id);
            $class->my_booking_id = $booking?->id;
            $class->my_status = $booking?->status;
        });

        return response()->json(['classes' => $classes]);
    }

    public function book(Request $request, GymClass $class)
    {
        abort_unless($class->gym_id === $request->user()->gym_id, 404);

        try {
            $booking = $class->book($request->user());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['booking' => $booking]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        abort_unless($booking->member_id === $request->user()->id, 404);

        $booking->gymClass->cancelBooking($booking);

        return response()->json(['message' => 'Booking cancelled.']);
    }
}
