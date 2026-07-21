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
        Schema::table('gyms', function (Blueprint $table) {
            $table->string('subscription_status')->default('trial');
            $table->string('plan_name')->nullable();
            $table->decimal('plan_price', 10, 2)->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspended_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_status',
                'plan_name',
                'plan_price',
                'billing_cycle',
                'trial_ends_at',
                'suspended_at',
                'suspended_reason',
            ]);
        });
    }
};
