<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white rounded shadow p-6">
        <label class="block text-sm font-medium mb-1">Member</label>
        <select wire:model.live="member_id" class="border rounded p-2 w-full max-w-sm">
            @foreach ($members as $member)
                <option value="{{ $member->id }}">{{ $member->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold mb-4">Progress Chart</h2>
        @if ($measurements->isEmpty())
            <p class="text-gray-500">No measurements logged yet for this member.</p>
        @else
            <div wire:key="chart-{{ $member_id }}" x-data="progressChart(@js($measurements->map(fn ($m) => [
                'recorded_at' => $m->recorded_at->format('Y-m-d'),
                'weight_kg' => $m->weight_kg,
                'body_fat_percentage' => $m->body_fat_percentage,
            ])))" x-init="init()" x-on:livewire:navigating.window="destroy()">
                <canvas x-ref="canvas" height="100"></canvas>
            </div>
        @endif
    </div>

    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold mb-4">Log New Measurement</h2>
        <form wire:submit="log" class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm mb-1">Date</label>
                <input type="date" wire:model="recorded_at" class="border rounded p-2 w-full">
                @error('recorded_at') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm mb-1">Weight (kg)</label>
                <input type="number" step="0.01" wire:model="weight_kg" class="border rounded p-2 w-full">
            </div>
            <div>
                <label class="block text-sm mb-1">Body fat (%)</label>
                <input type="number" step="0.01" wire:model="body_fat_percentage" class="border rounded p-2 w-full">
            </div>
            <div>
                <label class="block text-sm mb-1">Chest (cm)</label>
                <input type="number" step="0.01" wire:model="chest_cm" class="border rounded p-2 w-full">
            </div>
            <div>
                <label class="block text-sm mb-1">Waist (cm)</label>
                <input type="number" step="0.01" wire:model="waist_cm" class="border rounded p-2 w-full">
            </div>
            <div>
                <label class="block text-sm mb-1">Hips (cm)</label>
                <input type="number" step="0.01" wire:model="hips_cm" class="border rounded p-2 w-full">
            </div>
            <div>
                <label class="block text-sm mb-1">Arms (cm)</label>
                <input type="number" step="0.01" wire:model="arms_cm" class="border rounded p-2 w-full">
            </div>
            <div class="col-span-2 md:col-span-4">
                <label class="block text-sm mb-1">Notes</label>
                <textarea wire:model="notes" class="border rounded p-2 w-full" rows="2"></textarea>
            </div>
            <div class="col-span-2 md:col-span-4">
                <button type="submit" class="bg-gray-900 text-white rounded px-4 py-2 text-sm">Save Measurement</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="p-3">Date</th>
                    <th class="p-3">Weight (kg)</th>
                    <th class="p-3">Body fat (%)</th>
                    <th class="p-3">Chest (cm)</th>
                    <th class="p-3">Waist (cm)</th>
                    <th class="p-3">Hips (cm)</th>
                    <th class="p-3">Arms (cm)</th>
                    <th class="p-3">Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($measurements->sortByDesc('recorded_at') as $entry)
                    <tr class="border-b">
                        <td class="p-3">{{ $entry->recorded_at->format('Y-m-d') }}</td>
                        <td class="p-3">{{ $entry->weight_kg }}</td>
                        <td class="p-3">{{ $entry->body_fat_percentage }}</td>
                        <td class="p-3">{{ $entry->chest_cm }}</td>
                        <td class="p-3">{{ $entry->waist_cm }}</td>
                        <td class="p-3">{{ $entry->hips_cm }}</td>
                        <td class="p-3">{{ $entry->arms_cm }}</td>
                        <td class="p-3">{{ $entry->notes }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-3 text-gray-500" colspan="8">No measurements yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
