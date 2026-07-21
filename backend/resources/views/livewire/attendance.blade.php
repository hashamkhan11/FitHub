<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="fh-card max-w-xl" x-data="qrScanner">
        <div class="flex items-center justify-between mb-4">
            <h2 class="fh-heading">Check-In / Check-Out Scanner</h2>
            <span class="fh-eyebrow">Currently in gym: <span class="font-mono text-sm text-ink normal-case tracking-normal font-semibold">{{ $currentlyIn }}</span></span>
        </div>

        <div id="qr-reader" wire:ignore class="w-full max-w-sm mx-auto border border-chalk-2 rounded overflow-hidden"></div>

        <p class="mt-3 text-center fh-eyebrow">Scan once to check in, scan again to check out</p>

        @if ($lastMessage)
            <p class="mt-4 text-center font-display uppercase tracking-wide text-sm {{ $lastSuccess ? 'text-turf' : 'text-tape' }}">
                {{ $lastMessage }}
            </p>
        @endif
    </div>

    <div class="fh-card-flush">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="fh-th">Member</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Checked in at</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Checked out at</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recent as $entry)
                    <tr>
                        <td class="fh-td font-medium">{{ $entry->member->name }}</td>
                        <td class="fh-td-mono">{{ $entry->checked_in_at->format('Y-m-d H:i:s') }}</td>
                        <td class="fh-td-mono">{{ $entry->checked_out_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel" colspan="3">No check-ins yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
