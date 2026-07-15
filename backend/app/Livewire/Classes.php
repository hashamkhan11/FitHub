<?php

namespace App\Livewire;

use App\Models\GymClass;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Classes extends Component
{
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $instructor_name = '';

    #[Validate('required|date')]
    public string $start_time = '';

    #[Validate('required|integer|min:1')]
    public int $duration_minutes = 60;

    #[Validate('required|integer|min:1')]
    public int $capacity = 10;

    public bool $is_active = true;

    public function render()
    {
        return view('livewire.classes', [
            'classes' => GymClass::where('gym_id', auth()->user()->gym_id)
                ->withCount([
                    'bookings as booked_count' => fn ($query) => $query->where('status', 'booked'),
                    'bookings as waitlisted_count' => fn ($query) => $query->where('status', 'waitlisted'),
                ])
                ->orderBy('start_time')
                ->get(),
        ]);
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'instructor_name' => $this->instructor_name ?: null,
            'start_time' => $this->start_time,
            'duration_minutes' => $this->duration_minutes,
            'capacity' => $this->capacity,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            GymClass::where('gym_id', auth()->user()->gym_id)
                ->findOrFail($this->editingId)
                ->update($data);
        } else {
            GymClass::create([...$data, 'gym_id' => auth()->user()->gym_id]);
        }

        $this->resetForm();
    }

    public function edit(int $classId): void
    {
        $class = GymClass::where('gym_id', auth()->user()->gym_id)->findOrFail($classId);

        $this->editingId = $class->id;
        $this->name = $class->name;
        $this->instructor_name = $class->instructor_name ?? '';
        $this->start_time = $class->start_time->format('Y-m-d\TH:i');
        $this->duration_minutes = $class->duration_minutes;
        $this->capacity = $class->capacity;
        $this->is_active = $class->is_active;
    }

    public function delete(int $classId): void
    {
        GymClass::where('gym_id', auth()->user()->gym_id)->findOrFail($classId)->delete();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'instructor_name', 'start_time', 'duration_minutes', 'capacity', 'is_active']);
        $this->is_active = true;
        $this->duration_minutes = 60;
        $this->capacity = 10;
    }
}
