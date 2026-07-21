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
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'staff', 'trainer') DEFAULT 'owner'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'staff' WHERE role = 'trainer'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'staff') DEFAULT 'owner'");
    }
};
