<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('header_themes', function (Blueprint $table): void {
            $table->id();
            $table->string('site', 16)->default('adh');
            $table->string('slug');
            $table->json('name');
            $table->string('theme_type', 32)->default('fixed');
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedTinyInteger('day')->nullable();
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->tinyInteger('nth_week')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->integer('priority')->default(100);
            $table->boolean('is_enabled')->default(true);
            $table->string('mode', 32)->default('automatic');
            $table->json('banner_message')->nullable();
            $table->string('style_variant', 32)->nullable();
            $table->string('illustration_mode', 32)->default('inline_svg');
            $table->string('illustration_asset')->nullable();
            $table->boolean('show_flag')->default(false);
            $table->boolean('show_ataturk')->default(false);
            $table->string('decor_intensity', 16)->default('medium');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['site', 'slug']);
            $table->index(['site', 'is_enabled', 'mode', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('header_themes');
    }
};
