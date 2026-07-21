<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="fh-card max-w-2xl">
        <h2 class="fh-heading mb-4">{{ $editingId ? 'Edit Class' : 'New Class' }}</h2>

        <form wire:submit="save" class="grid grid-cols-2 gap-4">
            <div>
                <label class="fh-label">Name</label>
                <input type="text" wire:model="name" class="fh-input">
                @error('name') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Instructor</label>
                <input type="text" wire:model="instructor_name" class="fh-input">
                @error('instructor_name') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Start time</label>
                <input type="datetime-local" wire:model="start_time" class="fh-input">
                @error('start_time') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Duration (minutes)</label>
                <input type="number" wire:model="duration_minutes" class="fh-input">
                @error('duration_minutes') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Capacity</label>
                <input type="number" wire:model="capacity" class="fh-input">
                @error('capacity') <p class="fh-error">{{ $message }}</p> @enderror
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
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="fh-th">Name</th>
                    <th class="fh-th">Instructor</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Start time</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Capacity</th>
                    <th class="fh-th font-mono normal-case tracking-normal">No-shows</th>
                    <th class="fh-th">Status</th>
                    <th class="fh-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($classes as $class)
                    <tr>
                        <td class="fh-td font-medium">{{ $class->name }}</td>
                        <td class="fh-td text-steel">{{ $class->instructor_name ?? '—' }}</td>
                        <td class="fh-td-mono">{{ $class->start_time->format('D, M j g:ia') }}</td>
                        <td class="fh-td-mono">
                            {{ $class->booked_count }}/{{ $class->capacity }}
                            @if ($class->waitlisted_count > 0)
                                <span class="text-gold-2">(+{{ $class->waitlisted_count }})</span>
                            @endif
                        </td>
                        <td class="fh-td-mono">
                            @if ($class->start_time->isPast())
                                {{ $class->no_show_count }}/{{ $class->booked_count }}
                            @else
                                <span class="text-steel">—</span>
                            @endif
                        </td>
                        <td class="fh-td">
                            @if ($class->is_active)
                                <span class="fh-pill-good">Active</span>
                            @else
                                <span class="fh-pill-neutral">Inactive</span>
                            @endif
                        </td>
                        <td class="fh-td flex gap-3">
                            <button wire:click="edit({{ $class->id }})" class="fh-link-action text-gold-2">Edit</button>
                            <button wire:click="delete({{ $class->id }})" wire:confirm="Delete this class?" class="fh-link-action text-tape">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel" colspan="7">No classes yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
