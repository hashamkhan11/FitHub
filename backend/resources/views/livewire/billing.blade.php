<div class="max-w-[1000px] mx-auto space-y-6">
    <div>
        <h1 class="fh-heading text-2xl">Billing</h1>
        <p class="text-sm text-steel mt-1">Your gym's FitHub subscription.</p>
    </div>

    @error('subscribe') <div class="fh-card border-tape/40 text-sm text-tape">{{ $message }}</div> @enderror
    @error('manage') <div class="fh-card border-tape/40 text-sm text-tape">{{ $message }}</div> @enderror

    <div class="fh-card">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <p class="fh-label mb-1">Current Plan</p>
                @if ($gym->subscriptionPlan)
                    <p class="text-xl font-display font-semibold">{{ $gym->subscriptionPlan->name }}</p>
                @elseif ($gym->plan_name)
                    <p class="text-xl font-display font-semibold">{{ $gym->plan_name }}</p>
                    <p class="text-xs text-steel mt-1">Set manually by RankSol — not yet linked to a catalog plan.</p>
                @else
                    <p class="text-xl font-display font-semibold text-steel">No plan assigned</p>
                @endif
            </div>

            <div>
                @if ($gym->isSuspended())
                    <span class="fh-pill-bad">Suspended</span>
                @elseif ($gym->isOnTrial())
                    <span class="fh-pill-warn">Trial &middot; {{ $gym->trialDaysRemaining() }}d left</span>
                @else
                    <span class="fh-pill-good">Active</span>
                @endif
            </div>
        </div>

        @if ($subscription)
            <div class="border-t border-white/10 mt-5 pt-5 flex items-center justify-between flex-wrap gap-4">
                <div class="text-sm text-steel">
                    @if ($gym->pm_type)
                        <p>Card on file: {{ ucfirst($gym->pm_type) }} &middot;&middot;&middot;&middot; {{ $gym->pm_last_four }}</p>
                    @endif
                    @if ($subscription->ends_at)
                        <p>Cancels on {{ $subscription->ends_at->format('M j, Y') }}</p>
                    @elseif ($subscription->asStripeSubscription()->current_period_end ?? null)
                        <p>Renews {{ \Illuminate\Support\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('M j, Y') }}</p>
                    @endif
                </div>
                <button wire:click="manage" class="fh-btn-secondary">Manage Billing</button>
            </div>
        @endif
    </div>

    @unless ($subscription)
        <div>
            <h2 class="fh-heading text-sm mb-4">Choose a plan</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @forelse ($plans as $plan)
                    <div class="fh-card flex flex-col">
                        <p class="text-lg font-display font-semibold">{{ $plan->name }}</p>
                        @if ($plan->description)
                            <p class="text-sm text-steel mt-1">{{ $plan->description }}</p>
                        @endif

                        <div class="mt-4 space-y-1 text-sm">
                            <p class="fh-td-mono">${{ $plan->monthly_price }}<span class="text-steel text-xs">/mo</span></p>
                            @if ($plan->yearly_price)
                                <p class="fh-td-mono text-steel">${{ $plan->yearly_price }}<span class="text-xs">/yr</span></p>
                            @endif
                        </div>

                        @if ($plan->features)
                            <ul class="text-sm text-steel mt-4 space-y-1 flex-1">
                                @foreach ($plan->features as $feature)
                                    <li>&bull; {{ $feature }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div class="flex-1"></div>
                        @endif

                        <div class="mt-5 flex gap-2">
                            <button wire:click="subscribe({{ $plan->id }}, 'monthly')" wire:loading.attr="disabled" class="fh-btn-primary flex-1">
                                Subscribe Monthly
                            </button>
                            @if ($plan->yearly_price)
                                <button wire:click="subscribe({{ $plan->id }}, 'yearly')" wire:loading.attr="disabled" class="fh-btn-secondary flex-1">
                                    Yearly
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-steel">No plans are available yet — check back soon.</p>
                @endforelse
            </div>
        </div>
    @endunless
</div>
