<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold mb-4">{{ $editingId ? 'Edit Class' : 'New Class' }}</h2>

        <form wire:submit="save" class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-1">Name</label>
                <input type="text" wire:model="name" class="w-full border rounded px-3 py-2">
                @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Instructor</label>
                <input type="text" wire:model="instructor_name" class="w-full border rounded px-3 py-2">
                @error('instructor_name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Start time</label>
                <input type="datetime-local" wire:model="start_time" class="w-full border rounded px-3 py-2">
                @error('start_time') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Duration (minutes)</label>
                <input type="number" wire:model="duration_minutes" class="w-full border rounded px-3 py-2">
                @error('duration_minutes') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Capacity</label>
                <input type="number" wire:model="capacity" class="w-full border rounded px-3 py-2">
                @error('capacity') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
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
                    <th class="p-3">Instructor</th>
                    <th class="p-3">Start time</th>
                    <th class="p-3">Capacity</th>
                    <th class="p-3">Status</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($classes as $class)
                    <tr class="border-b">
                        <td class="p-3">{{ $class->name }}</td>
                        <td class="p-3">{{ $class->instructor_name ?? '—' }}</td>
                        <td class="p-3">{{ $class->start_time->format('D, M j g:ia') }}</td>
                        <td class="p-3">
                            {{ $class->booked_count }}/{{ $class->capacity }}
                            @if ($class->waitlisted_count > 0)
                                <span class="text-amber-600">(+{{ $class->waitlisted_count }} waitlisted)</span>
                            @endif
                        </td>
                        <td class="p-3">{{ $class->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="p-3 flex gap-3">
                            <button wire:click="edit({{ $class->id }})" class="text-blue-600">Edit</button>
                            <button wire:click="delete({{ $class->id }})" wire:confirm="Delete this class?" class="text-red-600">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-3 text-gray-500" colspan="6">No classes yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
