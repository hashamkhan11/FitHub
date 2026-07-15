<?php

namespace App\Livewire;

use App\Models\Member;
use App\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Members extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:members,email')]
    public string $email = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('required|exists:plans,id')]
    public ?int $plan_id = null;

    #[Validate('required|date')]
    public string $start_date = '';

    public function mount(): void
    {
        $this->start_date = now()->toDateString();
    }

    public function render()
    {
        return view('livewire.members', [
            'members' => Member::where('gym_id', auth()->user()->gym_id)
                ->with(['memberships' => fn ($q) => $q->latest('end_date')->with('plan')])
                ->latest()
                ->get(),
            'plans' => Plan::where('gym_id', auth()->user()->gym_id)->where('is_active', true)->get(),
        ]);
    }

    public function enroll(): void
    {
        $this->validate();

        $plan = Plan::where('gym_id', auth()->user()->gym_id)->findOrFail($this->plan_id);

        DB::transaction(function () use ($plan) {
            $member = Member::create([
                'gym_id' => auth()->user()->gym_id,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password,
                'join_date' => now(),
            ]);

            $member->memberships()->create([
                'plan_id' => $plan->id,
                'start_date' => $this->start_date,
                'end_date' => Carbon::parse($this->start_date)->addDays($plan->duration_days),
                'payment_status' => 'pending',
                'price_paid' => $plan->price,
            ]);
        });

        $this->reset(['name', 'email', 'phone', 'password', 'plan_id']);
        $this->start_date = now()->toDateString();
    }
}
