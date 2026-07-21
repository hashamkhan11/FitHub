<?php

namespace App\Livewire\Platform;

use App\Mail\WelcomeGymOwnerMail;
use App\Models\Gym;
use App\Models\PlatformActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.platform')]
class GymShow extends Component
{
    public Gym $gym;

    #[Validate('required|string|max:100')]
    public string $plan_name = '';

    #[Validate('nullable|numeric|min:0')]
    public string $plan_price = '';

    #[Validate('required|in:monthly,yearly')]
    public string $billing_cycle = 'monthly';

    public string $suspend_reason = '';

    public function mount(Gym $gym): void
    {
        $this->gym = $gym;
        $this->plan_name = $gym->plan_name ?? '';
        $this->plan_price = $gym->plan_price ?? '';
        $this->billing_cycle = $gym->billing_cycle;
    }

    public function updatePlan(): void
    {
        $this->validate();

        $this->gym->update([
            'plan_name' => $this->plan_name,
            'plan_price' => $this->plan_price ?: null,
            'billing_cycle' => $this->billing_cycle,
        ]);

        PlatformActivityLog::record('gym.plan_updated', "Updated plan for {$this->gym->name} to {$this->plan_name}.", $this->gym);

        session()->flash('status', 'Plan updated.');
    }

    public function activate(): void
    {
        $this->gym->update([
            'subscription_status' => 'active',
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);

        PlatformActivityLog::record('gym.activated', "Marked {$this->gym->name} as active.", $this->gym);

        session()->flash('status', "{$this->gym->name} is now active.");
    }

    public function suspend(): void
    {
        $this->validate(['suspend_reason' => 'required|string|max:255']);

        $this->gym->update([
            'subscription_status' => 'suspended',
            'suspended_at' => now(),
            'suspended_reason' => $this->suspend_reason,
        ]);

        PlatformActivityLog::record('gym.suspended', "Suspended {$this->gym->name}: {$this->suspend_reason}", $this->gym);

        $this->suspend_reason = '';

        session()->flash('status', "{$this->gym->name} has been suspended.");
    }

    public function resendWelcome(): void
    {
        $owner = User::where('gym_id', $this->gym->id)->where('role', 'owner')->first();

        if (! $owner) {
            $this->addError('resend', 'This gym has no owner account.');

            return;
        }

        $temporaryPassword = Str::password(12);
        $owner->update(['password' => $temporaryPassword]);

        Mail::to($owner->email)->send(new WelcomeGymOwnerMail($this->gym, $owner->email, $temporaryPassword));

        PlatformActivityLog::record('gym.welcome_resent', "Reset password and resent welcome email for {$this->gym->name}.", $this->gym);

        session()->flash('status', "A new temporary password was emailed to {$owner->email}.");
    }

    public function render()
    {
        return view('livewire.platform.gym-show', [
            'owner' => User::where('gym_id', $this->gym->id)->where('role', 'owner')->first(),
            'staffCount' => User::where('gym_id', $this->gym->id)->count(),
            'memberCount' => $this->gym->members()->count(),
            'activity' => PlatformActivityLog::where('gym_id', $this->gym->id)
                ->with('platformAdmin')
                ->latest('created_at')
                ->take(15)
                ->get(),
        ]);
    }
}
