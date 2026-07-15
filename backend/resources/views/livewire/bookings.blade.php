<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded shadow p-6">
        <label class="block text-sm mb-1">Class</label>
        <select wire:change="selectClass($event.target.value)" class="w-full border rounded px-3 py-2">
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected($classId === $class->id)>
                    {{ $class->name }} — {{ $class->start_time->format('D, M j g:ia') }}
                </option>
            @endforeach
        </select>
    </div>

    @if ($selectedClass)
        <div class="bg-white rounded shadow p-6">
            <h2 class="font-semibold mb-4">Add booking to {{ $selectedClass->name }}</h2>

            <form wire:submit="addBooking" class="flex gap-3 items-start">
                <div class="flex-1">
                    <select wire:model="memberId" class="w-full border rounded px-3 py-2">
                        <option value="">Select member…</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                    @error('memberId') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="bg-blue-600 text-white rounded px-4 py-2">Book</button>
            </form>
        </div>

        <div class="bg-white rounded shadow">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="p-3">Member</th>
                        <th class="p-3">Status</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr class="border-b">
                            <td class="p-3">{{ $booking->member->name }}</td>
                            <td class="p-3">
                                @if ($booking->status === 'booked')
                                    <span class="text-green-700">Booked</span>
                                @else
                                    <span class="text-amber-600">Waitlisted</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <button wire:click="cancelBooking({{ $booking->id }})" wire:confirm="Cancel this booking?" class="text-red-600">
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="p-3 text-gray-500" colspan="3">No bookings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <p class="text-gray-500">No classes yet — create one on the Classes page first.</p>
    @endif
</div>
