<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->enum('position', ['header', 'sidebar', 'inline', 'article_top', 'article_bottom', 'footer']);
            $table->enum('type', ['banner', 'adsense'])->default('banner');
            $table->string('image_path', 500)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->string('adsense_slot', 100)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('click_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamps();

            $table->index('position');
            $table->index('is_active');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
