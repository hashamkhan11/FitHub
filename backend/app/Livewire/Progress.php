<?php

namespace App\Livewire;

use App\Models\Measurement;
use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Progress extends Component
{
    #[Validate('required|exists:members,id')]
    public ?int $member_id = null;

    #[Validate('required|date')]
    public string $recorded_at = '';

    #[Validate('nullable|numeric')]
    public ?float $weight_kg = null;

    #[Validate('nullable|numeric')]
    public ?float $body_fat_percentage = null;

    #[Validate('nullable|numeric')]
    public ?float $chest_cm = null;

    #[Validate('nullable|numeric')]
    public ?float $waist_cm = null;

    #[Validate('nullable|numeric')]
    public ?float $hips_cm = null;

    #[Validate('nullable|numeric')]
    public ?float $arms_cm = null;

    #[Validate('nullable|string')]
    public ?string $notes = null;

    public function mount(): void
    {
        $this->recorded_at = now()->toDateString();
        $this->member_id = Member::where('gym_id', auth()->user()->gym_id)->value('id');
    }

    public function render()
    {
        return view('livewire.progress', [
            'members' => Member::where('gym_id', auth()->user()->gym_id)->orderBy('name')->get(),
            'measurements' => $this->member_id
                ? Measurement::where('member_id', $this->member_id)->orderBy('recorded_at')->get()
                : collect(),
        ]);
    }

    public function log(): void
    {
        $this->validate();

        Measurement::create([
            'gym_id' => auth()->user()->gym_id,
            'member_id' => $this->member_id,
            'recorded_at' => $this->recorded_at,
            'weight_kg' => $this->weight_kg,
            'body_fat_percentage' => $this->body_fat_percentage,
            'chest_cm' => $this->chest_cm,
            'waist_cm' => $this->waist_cm,
            'hips_cm' => $this->hips_cm,
            'arms_cm' => $this->arms_cm,
            'notes' => $this->notes,
        ]);

        $this->reset(['weight_kg', 'body_fat_percentage', 'chest_cm', 'waist_cm', 'hips_cm', 'arms_cm', 'notes']);
        $this->recorded_at = now()->toDateString();
    }
}
