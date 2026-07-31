<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Grandfather in every user that existed before self-serve signup
     * introduced email verification, so nobody gets nagged for an
     * account they didn't just create themselves.
     */
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Intentionally irreversible — we don't know which rows were
        // genuinely verified vs. backfilled by this migration.
    }
};
