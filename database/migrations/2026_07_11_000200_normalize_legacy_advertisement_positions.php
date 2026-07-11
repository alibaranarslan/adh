<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('advertisements')) {
            return;
        }

        DB::table('advertisements')
            ->where('position', 'sidebar')
            ->update(['position' => 'sidebar-top']);

        DB::table('advertisements')
            ->where('position', 'inline')
            ->update(['position' => 'article-top']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('advertisements')) {
            return;
        }

        DB::table('advertisements')
            ->where('position', 'sidebar-top')
            ->where(function ($query): void {
                $query->where('name', 'like', '%Sidebar%')
                    ->orWhere('name', 'like', '%Sidebar%');
            })
            ->update(['position' => 'sidebar']);

        DB::table('advertisements')
            ->where('position', 'article-top')
            ->where('name', 'like', '%Haber İçi%')
            ->update(['position' => 'inline']);
    }
};
