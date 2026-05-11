<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->string('position', 50)->change();
        });

        DB::table('advertisements')->where('position', 'sidebar')->update(['position' => 'sidebar-top']);
        DB::table('advertisements')->where('position', 'inline')->update(['position' => 'between-news']);
        DB::table('advertisements')->where('position', 'article_top')->update(['position' => 'article-top']);
        DB::table('advertisements')->where('position', 'article_bottom')->update(['position' => 'article-bottom']);
    }

    public function down(): void
    {
        DB::table('advertisements')->where('position', 'sidebar-top')->update(['position' => 'sidebar']);
        DB::table('advertisements')->where('position', 'between-news')->update(['position' => 'inline']);
        DB::table('advertisements')->where('position', 'article-top')->update(['position' => 'article_top']);
        DB::table('advertisements')->where('position', 'article-bottom')->update(['position' => 'article_bottom']);

        Schema::table('advertisements', function (Blueprint $table) {
            $table->enum('position', ['header', 'sidebar', 'inline', 'article_top', 'article_bottom', 'footer'])->change();
        });
    }
};
