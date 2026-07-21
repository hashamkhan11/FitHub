<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\LockDevice;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class LockDevices extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    public ?string $revealedToken = null;

    public ?int $revealedDeviceId = null;

    public function mount(): void
    {
        Gate::authorize('trigger-lock');
    }

    public function render()
    {
        return view('livewire.lock-devices', [
            'devices' => LockDevice::where('gym_id', auth()->user()->gym_id)
                ->withCount(['commands as pending_count' => fn ($query) => $query->where('status', 'pending')])
                ->orderBy('name')
                ->get(),
            'canManage' => Gate::allows('manage-lock-devices'),
        ]);
    }

    public function addDevice(): void
    {
        Gate::authorize('manage-lock-devices');

        $this->validate();

        [$device, $token] = LockDevice::issueToken(auth()->user()->gym_id, $this->name);

        $this->revealedDeviceId = $device->id;
        $this->revealedToken = $token;

        ActivityLog::record('lock.device_added', "Registered lock device \"{$device->name}\".");

        $this->reset('name');
    }

    public function regenerateToken(int $deviceId): void
    {
        Gate::authorize('manage-lock-devices');

        $device = LockDevice::where('gym_id', auth()->user()->gym_id)->findOrFail($deviceId);

        $this->revealedDeviceId = $device->id;
        $this->revealedToken = $device->regenerateToken();

        ActivityLog::record('lock.device_token_rotated', "Rotated the access token for lock device \"{$device->name}\".");
    }

    public function dismissToken(): void
    {
        $this->reset(['revealedToken', 'revealedDeviceId']);
    }

    public function deleteDevice(int $deviceId): void
    {
        Gate::authorize('manage-lock-devices');

        $device = LockDevice::where('gym_id', auth()->user()->gym_id)->findOrFail($deviceId);
        $name = $device->name;
        $device->delete();

        ActivityLog::record('lock.device_removed', "Removed lock device \"{$name}\".");
    }

    public function triggerUnlock(int $deviceId): void
    {
        Gate::authorize('trigger-lock');

        $device = LockDevice::where('gym_id', auth()->user()->gym_id)->findOrFail($deviceId);

        $device->commands()->create([
            'action' => 'open',
            'status' => 'pending',
            'requester_type' => User::class,
            'requester_id' => auth()->id(),
        ]);

        ActivityLog::record('lock.unlock_triggered', "Unlocked \"{$device->name}\" from the dashboard.");
    }
}
