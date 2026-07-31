<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Structural backstop for tenant isolation. Every gym-scoped controller/Livewire
 * component already filters `where('gym_id', ...)` by hand — this trait makes that
 * filter automatic for any query made while a gym owner/staff member (the 'web'
 * guard) is authenticated, so a future query that forgets the manual filter still
 * can't cross into another gym's data. It deliberately only activates for the 'web'
 * guard: console commands, queue workers, the 'platform' guard (RankSol admins, who
 * legitimately need to see any gym), and the Member-facing 'sanctum' guard are all
 * unaffected and keep relying on their existing explicit filters.
 */
trait BelongsToGym
{
    protected static function bootBelongsToGym(): void
    {
        static::addGlobalScope('gym', function (Builder $builder) {
            if (Auth::guard('web')->check()) {
                $builder->where(
                    $builder->getModel()->getTable().'.gym_id',
                    Auth::guard('web')->user()->gym_id
                );
            }
        });

        static::creating(function ($model) {
            if (empty($model->gym_id) && Auth::guard('web')->check()) {
                $model->gym_id = Auth::guard('web')->user()->gym_id;
            }
        });
    }
}
