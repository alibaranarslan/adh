<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iha_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['running', 'success', 'partial', 'failed'])->index();
            $table->integer('articles_fetched')->default(0);
            $table->integer('articles_created')->default(0);
            $table->integer('articles_updated')->default(0);
            $table->integer('articles_skipped')->default(0);
            $table->integer('images_downloaded')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['status', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iha_sync_logs');
    }
};
