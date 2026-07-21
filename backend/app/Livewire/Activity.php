<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Activity extends Component
{
    use WithPagination;

    public function mount(): void
    {
        Gate::authorize('view-activity');
    }

    public function render()
    {
        return view('livewire.activity', [
            'logs' => ActivityLog::where('gym_id', auth()->user()->gym_id)
                ->with('user')
                ->latest('created_at')
                ->paginate(30),
        ]);
    }
}
