<?php

namespace App\Livewire;

use App\Models\Attendance as AttendanceModel;
use App\Models\Member;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Attendance extends Component
{
    public ?string $lastMessage = null;

    public bool $lastSuccess = false;

    public function mount(): void
    {
        Gate::authorize('view-attendance');
    }

    public function render()
    {
        return view('livewire.attendance', [
            'recent' => AttendanceModel::where('gym_id', auth()->user()->gym_id)
                ->with('member')
                ->latest('checked_in_at')
                ->limit(20)
                ->get(),
            'currentlyIn' => AttendanceModel::where('gym_id', auth()->user()->gym_id)
                ->whereNull('checked_out_at')
                ->whereDate('checked_in_at', now()->toDateString())
                ->count(),
        ]);
    }

    public function checkIn(string $code): void
    {
        Gate::authorize('checkin-attendance');

        $member = Member::where('gym_id', auth()->user()->gym_id)
            ->where('qr_code', $code)
            ->first();

        if (! $member) {
            $this->lastSuccess = false;
            $this->lastMessage = 'QR code not recognized.';

            return;
        }

        $openAttendance = $member->attendances()
            ->whereNull('checked_out_at')
            ->whereDate('checked_in_at', now()->toDateString())
            ->latest('checked_in_at')
            ->first();

        if ($openAttendance) {
            $openAttendance->update(['checked_out_at' => now()]);
            $this->lastSuccess = true;
            $this->lastMessage = "Checked out: {$member->name}";

            return;
        }

        $latestMembership = $member->memberships()->latest('end_date')->first();

        if (! $latestMembership?->isActive()) {
            $this->lastSuccess = false;
            $this->lastMessage = "{$member->name} has no active membership.";

            return;
        }

        $member->attendances()->create([
            'gym_id' => $member->gym_id,
            'checked_in_at' => now(),
        ]);

        $this->lastSuccess = true;
        $this->lastMessage = "Checked in: {$member->name}";
    }
}
