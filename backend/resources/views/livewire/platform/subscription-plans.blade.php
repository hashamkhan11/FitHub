<div class="max-w-[1200px] mx-auto space-y-6">
    <div>
        <h1 class="pf-heading text-2xl">Subscription Plans</h1>
        <p class="text-sm text-mist mt-1">RankSol's own sellable tiers — what gyms pay to use FitHub.</p>
    </div>

    @if (session('status'))
        <div class="pf-card border-teal/30 bg-teal/5 text-sm text-teal">{{ session('status') }}</div>
    @endif
    @error('sync') <div class="pf-card border-tape/30 bg-tape/5 text-sm text-tape">{{ $message }}</div> @enderror

    <div class="pf-card max-w-2xl">
        <h2 class="pf-heading text-sm mb-4">{{ $editingId ? 'Edit Plan' : 'New Plan' }}</h2>

        <form wire:submit="save" class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="pf-label">Name</label>
                <input type="text" wire:model="name" class="pf-input">
                @error('name') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-2">
                <label class="pf-label">Description</label>
                <input type="text" wire:model="description" class="pf-input" placeholder="Short one-line pitch for this tier">
                @error('description') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="pf-label">Monthly Price (USD)</label>
                <input type="text" wire:model="monthly_price" class="pf-input">
                @error('monthly_price') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="pf-label">Yearly Price (USD)</label>
                <input type="text" wire:model="yearly_price" class="pf-input" placeholder="Optional">
                @error('yearly_price') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="pf-label">Member Limit</label>
                <input type="text" wire:model="member_limit" class="pf-input" placeholder="Blank = unlimited">
                @error('member_limit') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="pf-label">Staff Limit</label>
                <input type="text" wire:model="staff_limit" class="pf-input" placeholder="Blank = unlimited">
                @error('staff_limit') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-2">
                <label class="pf-label">Features (one per line)</label>
                <textarea wire:model="features" rows="4" class="pf-input" placeholder="Unlimited classes&#10;SMS reminders&#10;Lock/access control"></textarea>
                @error('features') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" id="is_active" class="accent-teal">
                <label for="is_active" class="text-sm text-ink">Active (visible to gyms)</label>
            </div>

            <div class="col-span-2 flex gap-2">
                <button type="submit" class="pf-btn-primary">
                    {{ $editingId ? 'Update' : 'Create' }}
                </button>

                @if ($editingId)
                    <button type="button" wire:click="resetForm" class="pf-btn-secondary">
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div class="pf-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="pf-th">Name</th>
                    <th class="pf-th">Monthly</th>
                    <th class="pf-th">Yearly</th>
                    <th class="pf-th">Stripe</th>
                    <th class="pf-th">Status</th>
                    <th class="pf-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td class="pf-td font-medium">{{ $plan->name }}</td>
                        <td class="pf-td-mono">${{ $plan->monthly_price }}</td>
                        <td class="pf-td-mono">{{ $plan->yearly_price ? '$'.$plan->yearly_price : '—' }}</td>
                        <td class="pf-td">
                            @if ($plan->isSyncedToStripe())
                                <span class="pf-pill-good">Synced</span>
                            @else
                                <span class="pf-pill-neutral">Not synced</span>
                            @endif
                        </td>
                        <td class="pf-td">
                            @if ($plan->is_active)
                                <span class="pf-pill-good">Active</span>
                            @else
                                <span class="pf-pill-neutral">Inactive</span>
                            @endif
                        </td>
                        <td class="pf-td flex gap-3">
                            <button wire:click="edit({{ $plan->id }})" class="text-teal hover:underline text-xs font-display uppercase tracking-wide">Edit</button>
                            <button wire:click="syncToStripe({{ $plan->id }})" wire:loading.attr="disabled" class="text-mist hover:text-ink hover:underline text-xs font-display uppercase tracking-wide">
                                {{ $plan->isSyncedToStripe() ? 'Re-sync' : 'Sync to Stripe' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="pf-td text-mist" colspan="6">No subscription plans yet — create one above.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
