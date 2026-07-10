<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table): void {
            $table->string('homepage_pin_area')->nullable()->after('editorial_score');
            $table->timestamp('homepage_pin_until')->nullable()->after('homepage_pin_area');
            $table->timestamp('homepage_exclude_until')->nullable()->after('homepage_pin_until');
            $table->json('editorial_score_breakdown')->nullable()->after('homepage_exclude_until');

            $table->index(['homepage_pin_area', 'homepage_pin_until'], 'news_homepage_pin_area_until_idx');
            $table->index('homepage_exclude_until', 'news_homepage_exclude_until_idx');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table): void {
            $table->dropIndex('news_homepage_pin_area_until_idx');
            $table->dropIndex('news_homepage_exclude_until_idx');
            $table->dropColumn([
                'homepage_pin_area',
                'homepage_pin_until',
                'homepage_exclude_until',
                'editorial_score_breakdown',
            ]);
        });
    }
};
