<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Booking;
use App\Models\GymClass;
use App\Models\Membership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Insight extends Component
{
    public function mount(): void
    {
        Gate::authorize('view-insight');
    }

    public function render()
    {
        $gymId = auth()->user()->gym_id;

        return view('livewire.insight', [
            'dailyCheckIns' => $this->dailyCheckIns($gymId),
            'peakHours' => $this->peakHours($gymId),
            'membershipCounts' => $this->membershipCounts($gymId),
            'renewalRate' => $this->renewalRate($gymId),
            'classStats' => $this->classStats($gymId),
            'noShowRate' => $this->noShowRate($gymId),
            'revenueByPlan' => $this->revenueByPlan($gymId),
            'outstandingBalances' => $this->outstandingBalances($gymId),
        ]);
    }

    private function dailyCheckIns(int $gymId): array
    {
        $rows = DB::table('attendances')
            ->selectRaw('DATE(checked_in_at) as day, COUNT(*) as total')
            ->where('gym_id', $gymId)
            ->where('checked_in_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $days = collect(range(0, 13))->map(fn ($i) => now()->subDays(13 - $i)->toDateString());

        return $days->mapWithKeys(fn ($day) => [$day => (int) ($rows[$day] ?? 0)])->all();
    }

    private function peakHours(int $gymId): array
    {
        $rows = DB::table('attendances')
            ->selectRaw('HOUR(checked_in_at) as hour, COUNT(*) as total')
            ->where('gym_id', $gymId)
            ->where('checked_in_at', '>=', now()->subDays(30))
            ->groupBy('hour')
            ->pluck('total', 'hour');

        return collect(range(0, 23))->mapWithKeys(fn ($hour) => [$hour => (int) ($rows[$hour] ?? 0)])->all();
    }

    private function membershipCounts(int $gymId): array
    {
        $latestPerMember = Membership::query()
            ->whereIn('id', function ($query) use ($gymId) {
                $query->selectRaw('MAX(memberships.id)')
                    ->from('memberships')
                    ->join('members', 'members.id', '=', 'memberships.member_id')
                    ->where('members.gym_id', $gymId)
                    ->groupBy('memberships.member_id');
            })
            ->get();

        return [
            'active' => $latestPerMember->filter->isActive()->count(),
            'expired' => $latestPerMember->reject->isActive()->count(),
        ];
    }

    private function renewalRate(int $gymId): array
    {
        $expiredRecently = Membership::query()
            ->join('members', 'members.id', '=', 'memberships.member_id')
            ->where('members.gym_id', $gymId)
            ->whereBetween('memberships.end_date', [now()->subDays(30)->toDateString(), now()->toDateString()])
            ->select('memberships.*')
            ->get();

        $renewed = $expiredRecently->filter(function ($membership) {
            return Membership::where('member_id', $membership->member_id)
                ->where('start_date', '>=', $membership->end_date)
                ->exists();
        })->count();

        $expiredCount = $expiredRecently->count();

        return [
            'expired' => $expiredCount,
            'renewed' => $renewed,
            'rate' => $expiredCount > 0 ? round(($renewed / $expiredCount) * 100) : null,
        ];
    }

    private function classStats(int $gymId): array
    {
        $classes = DB::table('gym_classes')
            ->leftJoin('bookings', function ($join) {
                $join->on('bookings.gym_class_id', '=', 'gym_classes.id')
                    ->where('bookings.status', '=', 'booked');
            })
            ->where('gym_classes.gym_id', $gymId)
            ->groupBy('gym_classes.id', 'gym_classes.name', 'gym_classes.capacity', 'gym_classes.start_time')
            ->select('gym_classes.id', 'gym_classes.name', 'gym_classes.capacity', 'gym_classes.start_time')
            ->selectRaw('COUNT(bookings.id) as booked_count')
            ->orderByDesc('booked_count')
            ->get();

        $fillRates = $classes->map(fn ($class) => $class->capacity > 0
            ? round(($class->booked_count / $class->capacity) * 100)
            : 0);

        return [
            'averageFillRate' => $fillRates->isNotEmpty() ? round($fillRates->avg()) : null,
            'mostPopular' => $classes->take(5),
        ];
    }

    /**
     * A booked member counts as attended if they checked into the gym on the
     * class's date — there's no per-class scan, only the front-desk QR check-in.
     */
    private function noShowRate(int $gymId): array
    {
        $classes = GymClass::where('gym_id', $gymId)
            ->whereBetween('start_time', [now()->subDays(30), now()])
            ->with(['bookings' => fn ($query) => $query->where('status', 'booked')])
            ->get();

        $memberIds = $classes->flatMap->bookings->pluck('member_id')->unique();

        $attendedDatesByMember = Attendance::where('gym_id', $gymId)
            ->whereIn('member_id', $memberIds)
            ->get()
            ->groupBy('member_id')
            ->map(fn ($rows) => $rows->pluck('checked_in_at')->map->toDateString()->unique());

        $booked = 0;
        $noShows = 0;

        foreach ($classes as $class) {
            $classDate = $class->start_time->toDateString();

            foreach ($class->bookings as $booking) {
                $booked++;

                if (! $attendedDatesByMember->get($booking->member_id, collect())->contains($classDate)) {
                    $noShows++;
                }
            }
        }

        return [
            'booked' => $booked,
            'noShows' => $noShows,
            'rate' => $booked > 0 ? round(($noShows / $booked) * 100) : null,
        ];
    }

    private function revenueByPlan(int $gymId): array
    {
        $rows = DB::table('payments')
            ->join('memberships', 'memberships.id', '=', 'payments.membership_id')
            ->join('plans', 'plans.id', '=', 'memberships.plan_id')
            ->where('payments.gym_id', $gymId)
            ->groupBy('plans.id', 'plans.name')
            ->select('plans.name')
            ->selectRaw('SUM(payments.amount) as total')
            ->orderByDesc('total')
            ->get();

        return [
            'byPlan' => $rows,
            'thisMonth' => DB::table('payments')
                ->where('gym_id', $gymId)
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
        ];
    }

    private function outstandingBalances(int $gymId)
    {
        return Membership::query()
            ->join('members', 'members.id', '=', 'memberships.member_id')
            ->where('members.gym_id', $gymId)
            ->whereIn('memberships.payment_status', ['pending', 'partial'])
            ->select('memberships.*')
            ->with('member', 'plan')
            ->orderBy('memberships.end_date')
            ->get();
    }
}
