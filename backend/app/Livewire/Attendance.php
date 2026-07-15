<?php

namespace App\Livewire;

use App\Models\Attendance as AttendanceModel;
use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Attendance extends Component
{
    public ?string $lastMessage = null;

    public bool $lastSuccess = false;

    public function render()
    {
        return view('livewire.attendance', [
            'recent' => AttendanceModel::where('gym_id', auth()->user()->gym_id)
                ->with('member')
                ->latest('checked_in_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function checkIn(string $code): void
    {
        $member = Member::where('gym_id', auth()->user()->gym_id)
            ->where('qr_code', $code)
            ->first();

        if (! $member) {
            $this->lastSuccess = false;
            $this->lastMessage = 'QR code not recognized.';

            return;
        }

        $recentlyCheckedIn = $member->attendances()
            ->where('checked_in_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($recentlyCheckedIn) {
            $this->lastSuccess = false;
            $this->lastMessage = "{$member->name} already checked in recently.";

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
