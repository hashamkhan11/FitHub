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
class GymCreate extends Component
{
    #[Validate('required|string|max:255')]
    public string $gym_name = '';

    #[Validate('required|email|max:255|unique:gyms,email')]
    public string $gym_email = '';

    #[Validate('nullable|string|max:20')]
    public string $gym_phone = '';

    #[Validate('required|string|max:255')]
    public string $owner_name = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $owner_email = '';

    #[Validate('required|string|max:100')]
    public string $plan_name = 'Starter';

    #[Validate('nullable|numeric|min:0')]
    public string $plan_price = '';

    #[Validate('required|in:monthly,yearly')]
    public string $billing_cycle = 'monthly';

    #[Validate('required|in:trial,active')]
    public string $subscription_status = 'trial';

    #[Validate('required|integer|min:1|max:365')]
    public int $trial_days = 14;

    public function save(): void
    {
        $this->validate();

        $temporaryPassword = Str::password(12);

        $gym = Gym::create([
            'name' => $this->gym_name,
            'email' => $this->gym_email,
            'phone' => $this->gym_phone ?: null,
            'currency_code' => 'PKR',
            'subscription_status' => $this->subscription_status,
            'plan_name' => $this->plan_name,
            'plan_price' => $this->plan_price ?: null,
            'billing_cycle' => $this->billing_cycle,
            'trial_ends_at' => $this->subscription_status === 'trial' ? now()->addDays($this->trial_days) : null,
        ]);

        User::create([
            'gym_id' => $gym->id,
            'name' => $this->owner_name,
            'email' => $this->owner_email,
            'password' => $temporaryPassword,
            'role' => 'owner',
        ]);

        Mail::to($this->owner_email)->send(new WelcomeGymOwnerMail($gym, $this->owner_email, $temporaryPassword));

        PlatformActivityLog::record('gym.created', "Onboarded new gym: {$gym->name}.", $gym);

        session()->flash('status', "{$gym->name} was created and a welcome email was sent to {$this->owner_email}.");

        $this->redirect("/ranksol/gyms/{$gym->id}", navigate: false);
    }
}
