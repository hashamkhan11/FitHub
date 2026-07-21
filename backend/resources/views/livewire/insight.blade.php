<div class="max-w-[1400px] mx-auto space-y-6">
    <div>
        <p class="fh-eyebrow">Reports</p>
        <h2 class="fh-heading text-xl">Insight Dashboard</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="fh-card"
             x-data="barChart(
                {{ Js::from(array_map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('M j'), array_keys($dailyCheckIns))) }},
                {{ Js::from(array_values($dailyCheckIns)) }},
                'Check-ins',
                '#D9A441',
                true
             )">
            <h3 class="fh-heading text-sm mb-4">Daily check-ins — last 14 days</h3>
            <canvas x-ref="canvas"></canvas>
        </div>

        <div class="fh-card"
             x-data="barChart(
                {{ Js::from(array_map(fn ($h) => sprintf('%02d:00', $h), array_keys($peakHours))) }},
                {{ Js::from(array_values($peakHours)) }},
                'Check-ins by hour (last 30 days)',
                '#5B6472',
                true
             )">
            <h3 class="fh-heading text-sm mb-4">Peak hours</h3>
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="fh-card">
            <p class="fh-eyebrow">Members</p>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="fh-stat-value text-turf">{{ $membershipCounts['active'] }}</span>
                <span class="text-sm text-steel">active</span>
            </div>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="fh-stat-value text-tape text-xl">{{ $membershipCounts['expired'] }}</span>
                <span class="text-sm text-steel">expired</span>
            </div>
        </div>

        <div class="fh-card">
            <p class="fh-eyebrow">Renewal rate — last 30 days</p>
            @if ($renewalRate['rate'] === null)
                <p class="text-sm text-steel mt-3">No expirations in this window.</p>
            @else
                <p class="fh-stat-value mt-2">{{ $renewalRate['rate'] }}%</p>
                <p class="text-sm text-steel mt-1">{{ $renewalRate['renewed'] }} of {{ $renewalRate['expired'] }} expired memberships renewed</p>
            @endif
        </div>

        <div class="fh-card">
            <p class="fh-eyebrow">Class fill rate</p>
            @if ($classStats['averageFillRate'] === null)
                <p class="text-sm text-steel mt-3">No classes yet.</p>
            @else
                <p class="fh-stat-value mt-2">{{ $classStats['averageFillRate'] }}%</p>
                <p class="text-sm text-steel mt-1">average across all classes</p>
            @endif
        </div>

        <div class="fh-card">
            <p class="fh-eyebrow">No-show rate — last 30 days</p>
            @if ($noShowRate['rate'] === null)
                <p class="text-sm text-steel mt-3">No past classes in this window.</p>
            @else
                <p class="fh-stat-value mt-2">{{ $noShowRate['rate'] }}%</p>
                <p class="text-sm text-steel mt-1">{{ $noShowRate['noShows'] }} of {{ $noShowRate['booked'] }} booked spots no-showed</p>
            @endif
        </div>
    </div>

    <div class="fh-card-flush p-0">
        <h3 class="fh-heading text-sm p-4 pb-0">Most popular classes</h3>
        <div class="overflow-x-auto">
        <table class="w-full mt-2">
            <thead>
                <tr>
                    <th class="fh-th">Class</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Start</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Booked</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Capacity</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($classStats['mostPopular'] as $class)
                    <tr>
                        <td class="fh-td font-medium">{{ $class->name }}</td>
                        <td class="fh-td-mono">{{ \Illuminate\Support\Carbon::parse($class->start_time)->format('M j, g:i A') }}</td>
                        <td class="fh-td-mono">{{ $class->booked_count }}</td>
                        <td class="fh-td-mono">{{ $class->capacity }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel" colspan="4">No classes yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="fh-card"
             x-data="barChart(
                {{ Js::from($revenueByPlan['byPlan']->pluck('name')) }},
                {{ Js::from($revenueByPlan['byPlan']->pluck('total')) }},
                'Revenue',
                '#2F5D50'
             )">
            <h3 class="fh-heading text-sm mb-4">Revenue by plan — all-time, paid</h3>
            <canvas x-ref="canvas"></canvas>
            <p class="text-sm text-steel mt-4">This month: <span class="font-mono font-semibold text-ink">{{ number_format($revenueByPlan['thisMonth'], 2) }}</span></p>
        </div>

        <div class="fh-card-flush p-0 flex flex-col">
            <h3 class="fh-heading text-sm p-4 pb-0">Outstanding balances</h3>
            <div class="overflow-x-auto">
            <table class="w-full mt-2">
                <thead>
                    <tr>
                        <th class="fh-th">Member</th>
                        <th class="fh-th">Plan</th>
                        <th class="fh-th font-mono normal-case tracking-normal">Amount</th>
                        <th class="fh-th">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($outstandingBalances as $membership)
                        <tr>
                            <td class="fh-td font-medium">{{ $membership->member->name }}</td>
                            <td class="fh-td">{{ $membership->plan->name }}</td>
                            <td class="fh-td-mono">{{ number_format($membership->balance_due, 2) }}</td>
                            <td class="fh-td">
                                @if ($membership->payment_status === 'partial')
                                    <span class="fh-pill-warn">Partial</span>
                                @else
                                    <span class="fh-pill-bad">Pending</span>
                                @endif
                                @if ($membership->isOverdue())
                                    <span class="text-tape text-xs block font-mono mt-1">overdue</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="fh-td text-steel" colspan="4">No outstanding balances.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
