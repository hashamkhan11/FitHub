<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gym extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'logo_path',
    ];

    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }
}
