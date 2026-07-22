<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\PlatformActivityLog;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    protected function handleCustomerSubscriptionUpdated(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $this->syncGymFromStripeEvent($payload);

        return $response;
    }

    protected function handleCustomerSubscriptionDeleted(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $this->syncGymFromStripeEvent($payload);

        return $response;
    }

    private function syncGymFromStripeEvent(array $payload): void
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;

        if (! $stripeCustomerId) {
            return;
        }

        $gym = Gym::where('stripe_id', $stripeCustomerId)->first();

        if (! $gym) {
            return;
        }

        $stripeStatus = $payload['data']['object']['status'] ?? null;
        $status = match ($stripeStatus) {
            'active', 'trialing' => 'active',
            'canceled', 'unpaid', 'incomplete_expired' => 'suspended',
            default => $gym->subscription_status,
        };

        $plan = $gym->subscriptionPlan;

        $gym->update([
            'subscription_status' => $status,
            'plan_name' => $plan->name ?? $gym->plan_name,
            'plan_price' => $plan->monthly_price ?? $gym->plan_price,
        ]);

        $description = "Stripe subscription for {$gym->name} is now '{$stripeStatus}'.";

        ActivityLog::create([
            'gym_id' => $gym->id,
            'user_id' => null,
            'action' => 'billing.subscription_updated',
            'description' => $description,
            'created_at' => now(),
        ]);

        PlatformActivityLog::create([
            'platform_admin_id' => null,
            'gym_id' => $gym->id,
            'action' => 'gym.subscription_updated',
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
