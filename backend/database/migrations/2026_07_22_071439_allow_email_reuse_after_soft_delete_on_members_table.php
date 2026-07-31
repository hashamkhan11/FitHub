<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // MySQL's unique index still blocks a new row from reusing an
            // email that belongs to a soft-deleted member, since the index
            // has no concept of deleted_at. Uniqueness is enforced at the
            // validation layer instead (scoped to exclude trashed rows), and
            // this becomes a plain index kept for lookup performance only.
            $table->dropUnique(['email']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->unique('email');
        });
    }
};
