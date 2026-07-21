<?php

namespace App\Livewire\Platform;

use App\Models\PlatformActivityLog;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.platform')]
class Activity extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.platform.activity', [
            'logs' => PlatformActivityLog::with(['platformAdmin', 'gym'])
                ->latest('created_at')
                ->paginate(30),
        ]);
    }
}
