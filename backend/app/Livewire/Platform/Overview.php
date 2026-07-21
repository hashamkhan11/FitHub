<?php

namespace App\Livewire\Platform;

use App\Models\Gym;
use App\Models\PlatformActivityLog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class Overview extends Component
{
    public function render()
    {
        $gyms = Gym::all();

        $mrr = $gyms
            ->where('subscription_status', '!=', 'suspended')
            ->sum(function (Gym $gym) {
                if (! $gym->plan_price) {
                    return 0;
                }

                return $gym->billing_cycle === 'yearly'
                    ? $gym->plan_price / 12
                    : $gym->plan_price;
            });

        return view('livewire.platform.overview', [
            'totalGyms' => $gyms->count(),
            'activeGyms' => $gyms->where('subscription_status', 'active')->count(),
            'trialGyms' => $gyms->where('subscription_status', 'trial')->count(),
            'suspendedGyms' => $gyms->where('subscription_status', 'suspended')->count(),
            'mrr' => $mrr,
            'recentGyms' => $gyms->sortByDesc('created_at')->take(5),
            'recentActivity' => PlatformActivityLog::with(['platformAdmin', 'gym'])
                ->latest('created_at')
                ->take(8)
                ->get(),
        ]);
    }
}
