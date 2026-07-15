<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold mb-4">{{ $editingId ? 'Edit Plan' : 'New Plan' }}</h2>

        <form wire:submit="save" class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-1">Name</label>
                <input type="text" wire:model="name" class="w-full border rounded px-3 py-2">
                @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Duration (days)</label>
                <input type="number" wire:model="duration_days" class="w-full border rounded px-3 py-2">
                @error('duration_days') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Price</label>
                <input type="text" wire:model="price" class="w-full border rounded px-3 py-2">
                @error('price') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-2 mt-6">
                <input type="checkbox" wire:model="is_active" id="is_active">
                <label for="is_active" class="text-sm">Active</label>
            </div>

            <div class="col-span-2 flex gap-2">
                <button type="submit" class="bg-blue-600 text-white rounded px-4 py-2">
                    {{ $editingId ? 'Update' : 'Create' }}
                </button>

                @if ($editingId)
                    <button type="button" wire:click="resetForm" class="border rounded px-4 py-2">
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="p-3">Name</th>
                    <th class="p-3">Duration</th>
                    <th class="p-3">Price</th>
                    <th class="p-3">Status</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr class="border-b">
                        <td class="p-3">{{ $plan->name }}</td>
                        <td class="p-3">{{ $plan->duration_days }} days</td>
                        <td class="p-3">{{ $plan->price }}</td>
                        <td class="p-3">{{ $plan->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="p-3 flex gap-3">
                            <button wire:click="edit({{ $plan->id }})" class="text-blue-600">Edit</button>
                            <button wire:click="delete({{ $plan->id }})" wire:confirm="Delete this plan?" class="text-red-600">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-3 text-gray-500" colspan="5">No plans yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
