<?php

namespace App\Livewire;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Billing extends Component
{
    public function mount(): void
    {
        Gate::authorize('manage-billing');
    }

    public function render()
    {
        $gym = auth()->user()->gym;

        return view('livewire.billing', [
            'gym' => $gym,
            'subscription' => $gym->subscription('default'),
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function subscribe(int $planId, string $cycle): void
    {
        $gym = auth()->user()->gym;
        $plan = SubscriptionPlan::findOrFail($planId);

        $priceId = $cycle === 'yearly' ? $plan->stripe_price_id_yearly : $plan->stripe_price_id_monthly;

        if (! $priceId) {
            $this->addError('subscribe', 'This plan has not been synced to Stripe yet. Contact RankSol support.');

            return;
        }

        try {
            $checkout = $gym->newSubscription('default', $priceId)->checkout([
                'success_url' => route('billing').'?checkout=success',
                'cancel_url' => route('billing').'?checkout=cancelled',
            ]);

            $gym->update(['subscription_plan_id' => $plan->id]);

            $this->redirect($checkout->asStripeCheckoutSession()->url);
        } catch (\Throwable $e) {
            $this->addError('subscribe', "Could not start checkout: {$e->getMessage()}");
        }
    }

    public function manage(): void
    {
        $gym = auth()->user()->gym;

        try {
            $this->redirect($gym->redirectToBillingPortal(route('billing'))->getTargetUrl());
        } catch (\Throwable $e) {
            $this->addError('manage', "Could not open the billing portal: {$e->getMessage()}");
        }
    }
}
