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
        Schema::create('lock_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lock_device_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('status')->default('pending');
            $table->nullableMorphs('requester');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['lock_device_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lock_commands');
    }
};
