<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_guide_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('guide_key', 120);
            $table->string('status', 32)->default('not_started');
            $table->unsignedSmallInteger('last_step_index')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'guide_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_guide_progress');
    }
};
