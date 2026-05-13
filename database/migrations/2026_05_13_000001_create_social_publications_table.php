<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_article_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 32)->default('instagram');
            $table->string('status', 32)->default('pending')->index();
            $table->text('caption')->nullable();
            $table->string('creative_image_path', 500)->nullable();
            $table->string('creative_image_url', 1000)->nullable();
            $table->string('container_id', 255)->nullable();
            $table->string('media_id', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['news_article_id', 'platform']);
            $table->index(['platform', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_publications');
    }
};
