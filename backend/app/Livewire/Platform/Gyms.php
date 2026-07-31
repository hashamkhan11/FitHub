<?php

namespace App\Livewire\Platform;

use App\Models\Gym;
use App\Models\PlatformActivityLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.platform')]
class Gyms extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function activate(int $gymId): void
    {
        $gym = Gym::findOrFail($gymId);

        $gym->update([
            'subscription_status' => 'active',
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);

        PlatformActivityLog::record('gym.activated', "Marked {$gym->name} as active.", $gym);

        session()->flash('status', "{$gym->name} is now active.");
    }

    public function suspend(int $gymId, ?string $reason): void
    {
        if (! $reason) {
            return;
        }

        $gym = Gym::findOrFail($gymId);

        $gym->update([
            'subscription_status' => 'suspended',
            'suspended_at' => now(),
            'suspended_reason' => $reason,
        ]);

        PlatformActivityLog::record('gym.suspended', "Suspended {$gym->name}: {$reason}", $gym);

        session()->flash('status', "{$gym->name} has been suspended.");
    }

    public function deleteGym(int $gymId): void
    {
        $gym = Gym::findOrFail($gymId);
        $name = $gym->name;

        // Logged before delete() - platform_activity_logs.gym_id is a foreign
        // key, so it can't reference a gym row that's already gone.
        PlatformActivityLog::record('gym.deleted', "Permanently deleted gym {$name} and all its data.", $gym);

        $gym->delete();

        session()->flash('status', "{$name} has been permanently deleted.");
    }

    public function render()
    {
        $gyms = Gym::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter, fn ($q) => $q->where('subscription_status', $this->statusFilter))
            ->withCount('members')
            ->latest()
            ->paginate(15);

        return view('livewire.platform.gyms', [
            'gyms' => $gyms,
        ]);
    }
}
