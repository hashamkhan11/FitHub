<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedInteger('member_code')->nullable()->after('id');
        });

        DB::table('members')->select('id', 'gym_id')->orderBy('gym_id')->orderBy('id')
            ->get()
            ->groupBy('gym_id')
            ->each(function ($members) {
                $next = 1;
                foreach ($members as $member) {
                    DB::table('members')->where('id', $member->id)->update(['member_code' => $next++]);
                }
            });

        Schema::table('members', function (Blueprint $table) {
            $table->unique(['gym_id', 'member_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['gym_id', 'member_code']);
            $table->dropColumn('member_code');
        });
    }
};
