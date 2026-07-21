<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('memberships')->where('payment_status', 'overdue')->update(['payment_status' => 'pending']);

        DB::statement("ALTER TABLE memberships MODIFY payment_status ENUM('pending', 'partial', 'paid') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('memberships')->where('payment_status', 'partial')->update(['payment_status' => 'pending']);

        DB::statement("ALTER TABLE memberships MODIFY payment_status ENUM('paid', 'pending', 'overdue') NOT NULL DEFAULT 'pending'");
    }
};
