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
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_at');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('body_fat_percentage', 4, 2)->nullable();
            $table->decimal('chest_cm', 5, 2)->nullable();
            $table->decimal('waist_cm', 5, 2)->nullable();
            $table->decimal('hips_cm', 5, 2)->nullable();
            $table->decimal('arms_cm', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
