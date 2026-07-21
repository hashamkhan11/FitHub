<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="fh-card max-w-2xl">
        <h2 class="fh-heading mb-4">{{ $editingId ? 'Edit Plan' : 'New Plan' }}</h2>

        <form wire:submit="save" class="grid grid-cols-2 gap-4">
            <div>
                <label class="fh-label">Name</label>
                <input type="text" wire:model="name" class="fh-input">
                @error('name') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Duration (days)</label>
                <input type="number" wire:model="duration_days" class="fh-input">
                @error('duration_days') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Price</label>
                <input type="text" wire:model="price" class="fh-input">
                @error('price') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-2 mt-6">
                <input type="checkbox" wire:model="is_active" id="is_active" class="accent-gold-2">
                <label for="is_active" class="text-sm">Active</label>
            </div>

            <div class="col-span-2 flex gap-2">
                <button type="submit" class="fh-btn-primary">
                    {{ $editingId ? 'Update' : 'Create' }}
                </button>

                @if ($editingId)
                    <button type="button" wire:click="resetForm" class="fh-btn-secondary">
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div class="fh-card-flush">
        @error('deletePlan')
            <p class="fh-error px-4 pt-4">{{ $message }}</p>
        @enderror
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="fh-th">Name</th>
                    <th class="fh-th">Duration</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Price</th>
                    <th class="fh-th">Status</th>
                    <th class="fh-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr>
                        <td class="fh-td font-medium">{{ $plan->name }}</td>
                        <td class="fh-td-mono">{{ $plan->duration_days }}d</td>
                        <td class="fh-td-mono">{{ $plan->price }}</td>
                        <td class="fh-td">
                            @if ($plan->is_active)
                                <span class="fh-pill-good">Active</span>
                            @else
                                <span class="fh-pill-neutral">Inactive</span>
                            @endif
                        </td>
                        <td class="fh-td flex gap-3">
                            <button wire:click="edit({{ $plan->id }})" class="fh-link-action text-gold-2">Edit</button>
                            <button wire:click="delete({{ $plan->id }})" wire:confirm="Delete this plan?" class="fh-link-action text-tape">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel" colspan="5">No plans yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
