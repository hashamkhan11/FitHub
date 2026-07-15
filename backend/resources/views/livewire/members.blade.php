<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold mb-4">Enroll New Member</h2>

        <form wire:submit="enroll" class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm mb-1">Name</label>
                <input type="text" wire:model="name" class="w-full border rounded px-3 py-2">
                @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Email</label>
                <input type="email" wire:model="email" class="w-full border rounded px-3 py-2">
                @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Phone</label>
                <input type="text" wire:model="phone" class="w-full border rounded px-3 py-2">
                @error('phone') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Password</label>
                <input type="password" wire:model="password" class="w-full border rounded px-3 py-2">
                @error('password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Plan</label>
                <select wire:model="plan_id" class="w-full border rounded px-3 py-2">
                    <option value="">Select a plan</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->duration_days }} days, {{ $plan->price }})</option>
                    @endforeach
                </select>
                @error('plan_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Start Date</label>
                <input type="date" wire:model="start_date" class="w-full border rounded px-3 py-2">
                @error('start_date') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-2">
                <button type="submit" class="bg-blue-600 text-white rounded px-4 py-2">
                    Enroll Member
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="p-3">Name</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Plan</th>
                    <th class="p-3">Ends</th>
                    <th class="p-3">Payment</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">QR</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    @php $membership = $member->memberships->first(); @endphp
                    <tr class="border-b">
                        <td class="p-3">{{ $member->name }}</td>
                        <td class="p-3">{{ $member->email }}</td>
                        <td class="p-3">{{ $membership?->plan?->name ?? '—' }}</td>
                        <td class="p-3">{{ $membership?->end_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="p-3">{{ $membership?->payment_status ?? '—' }}</td>
                        <td class="p-3">{{ $member->status }}</td>
                        <td class="p-3">
                            <a href="{{ route('members.qr', $member) }}" target="_blank">
                                <img src="{{ route('members.qr', $member) }}" width="48" height="48" alt="QR code">
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-3 text-gray-500" colspan="6">No members yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
