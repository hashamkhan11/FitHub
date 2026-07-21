<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <p class="pf-eyebrow">RankSol Platform</p>
            <h1 class="pf-heading text-2xl">Gyms</h1>
        </div>
        <a href="/ranksol/gyms/new" class="pf-btn-primary">+ New Gym</a>
    </div>

    <div class="flex gap-3 flex-wrap">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search by name or email…" class="pf-input max-w-xs">
        <select wire:model.live="statusFilter" class="pf-input max-w-[10rem]">
            <option value="">All statuses</option>
            <option value="trial">Trial</option>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
        </select>
    </div>

    <div class="pf-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="pf-th">Gym</th>
                    <th class="pf-th">Plan</th>
                    <th class="pf-th">Members</th>
                    <th class="pf-th">Status</th>
                    <th class="pf-th">Joined</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gyms as $gym)
                    <tr class="hover:bg-ink/5 transition cursor-pointer" onclick="window.location='/ranksol/gyms/{{ $gym->id }}'">
                        <td class="pf-td">
                            <p class="text-ink font-medium">{{ $gym->name }}</p>
                            <p class="text-xs text-mist">{{ $gym->email }}</p>
                        </td>
                        <td class="pf-td">{{ $gym->plan_name ?? '—' }}</td>
                        <td class="pf-td-mono">{{ $gym->members_count }}</td>
                        <td class="pf-td">
                            @if ($gym->isSuspended())
                                <span class="pf-pill-bad">Suspended</span>
                            @elseif ($gym->isOnTrial())
                                <span class="pf-pill-warn">Trial</span>
                            @else
                                <span class="pf-pill-good">Active</span>
                            @endif
                        </td>
                        <td class="pf-td-mono">{{ $gym->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="pf-td text-mist" colspan="5">No gyms match this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{ $gyms->links('platform.pagination') }}
</div>
