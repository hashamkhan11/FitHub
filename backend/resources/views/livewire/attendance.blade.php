<div class="max-w-3xl mx-auto space-y-6">
    <div class="bg-white rounded shadow p-6" x-data="qrScanner">
        <h2 class="font-semibold mb-4">Check-In Scanner</h2>

        <div id="qr-reader" wire:ignore class="w-full max-w-sm mx-auto"></div>

        @if ($lastMessage)
            <p class="mt-4 text-center font-medium {{ $lastSuccess ? 'text-green-600' : 'text-red-600' }}">
                {{ $lastMessage }}
            </p>
        @endif
    </div>

    <div class="bg-white rounded shadow">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="p-3">Member</th>
                    <th class="p-3">Checked in at</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recent as $entry)
                    <tr class="border-b">
                        <td class="p-3">{{ $entry->member->name }}</td>
                        <td class="p-3">{{ $entry->checked_in_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-3 text-gray-500" colspan="2">No check-ins yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
