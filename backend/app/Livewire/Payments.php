<?php

namespace App\Livewire;

use App\Models\Payment;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Payments extends Component
{
    use WithPagination;

    public string $search = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        Gate::authorize('manage-payments');
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $payments = Payment::query()
            ->whereHas('membership.member', fn ($q) => $q->withTrashed()->where('gym_id', auth()->user()->gym_id))
            ->with(['membership.member' => fn ($q) => $q->withTrashed(), 'membership.plan'])
            ->when($this->search !== '', function ($query) {
                $term = $this->search;
                $query->whereHas('membership.member', function ($q) use ($term) {
                    $q->withTrashed()
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('member_code', 'like', "%{$term}%");
                });
            })
            ->when($this->from !== '', fn ($query) => $query->whereDate('paid_at', '>=', $this->from))
            ->when($this->to !== '', fn ($query) => $query->whereDate('paid_at', '<=', $this->to))
            ->latest('paid_at')
            ->latest('id')
            ->paginate(20);

        return view('livewire.payments', [
            'payments' => $payments,
            'gym' => auth()->user()->gym,
        ]);
    }
}
