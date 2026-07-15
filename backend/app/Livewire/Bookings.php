<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\GymClass;
use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Bookings extends Component
{
    public ?int $classId = null;

    #[Validate('required|exists:members,id')]
    public ?int $memberId = null;

    public function mount(): void
    {
        $this->classId = GymClass::where('gym_id', auth()->user()->gym_id)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->value('id');
    }

    public function render()
    {
        $gymId = auth()->user()->gym_id;

        $selectedClass = $this->classId
            ? GymClass::where('gym_id', $gymId)->find($this->classId)
            : null;

        return view('livewire.bookings', [
            'classes' => GymClass::where('gym_id', $gymId)->orderBy('start_time')->get(),
            'selectedClass' => $selectedClass,
            'bookings' => $selectedClass
                ? $selectedClass->bookings()
                    ->with('member')
                    ->where('status', '!=', 'cancelled')
                    ->orderByRaw("status = 'waitlisted'")
                    ->oldest()
                    ->get()
                : collect(),
            'members' => Member::where('gym_id', $gymId)->orderBy('name')->get(),
        ]);
    }

    public function selectClass(int $classId): void
    {
        $this->classId = $classId;
    }

    public function addBooking(): void
    {
        $this->validate();

        $class = GymClass::where('gym_id', auth()->user()->gym_id)->findOrFail($this->classId);
        $member = Member::where('gym_id', auth()->user()->gym_id)->findOrFail($this->memberId);

        try {
            $class->book($member);
        } catch (\DomainException $e) {
            $this->addError('memberId', $e->getMessage());
            return;
        }

        $this->reset('memberId');
    }

    public function cancelBooking(int $bookingId): void
    {
        $class = GymClass::where('gym_id', auth()->user()->gym_id)->findOrFail($this->classId);
        $booking = $class->bookings()->findOrFail($bookingId);

        $class->cancelBooking($booking);
    }
}
