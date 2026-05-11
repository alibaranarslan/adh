<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($index) => ($index->name ?? null) === $indexName);
        }

        $result = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName]);

        return ! empty($result);
    }

    public function up(): void
    {
        if (! $this->hasIndex('news_articles', 'news_articles_author_id_idx')) {
            Schema::table('news_articles', function (Blueprint $table) {
                $table->index('author_id', 'news_articles_author_id_idx');
            });
        }

        if (! $this->hasIndex('news_articles', 'news_articles_view_count_idx')) {
            Schema::table('news_articles', function (Blueprint $table) {
                $table->index('view_count', 'news_articles_view_count_idx');
            });
        }

        if (! $this->hasIndex('analytics_page_views', 'analytics_page_views_type_id_viewed_at_idx')) {
            Schema::table('analytics_page_views', function (Blueprint $table) {
                $table->index(['viewable_type', 'viewable_id', 'viewed_at'], 'analytics_page_views_type_id_viewed_at_idx');
            });
        }

        if (! $this->hasIndex('analytics_page_views', 'analytics_page_views_ip_address_idx')) {
            Schema::table('analytics_page_views', function (Blueprint $table) {
                $table->index('ip_address', 'analytics_page_views_ip_address_idx');
            });
        }

        if (! $this->hasIndex('local_info_entries', 'local_info_entries_type_active_idx')) {
            Schema::table('local_info_entries', function (Blueprint $table) {
                $table->index(['type', 'is_active'], 'local_info_entries_type_active_idx');
            });
        }

        if (! $this->hasIndex('advertisements', 'advertisements_active_position_idx')) {
            Schema::table('advertisements', function (Blueprint $table) {
                $table->index(['is_active', 'position'], 'advertisements_active_position_idx');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('news_articles', 'news_articles_author_id_idx')) {
            Schema::table('news_articles', function (Blueprint $table) {
                $table->dropIndex('news_articles_author_id_idx');
            });
        }

        if ($this->hasIndex('news_articles', 'news_articles_view_count_idx')) {
            Schema::table('news_articles', function (Blueprint $table) {
                $table->dropIndex('news_articles_view_count_idx');
            });
        }

        if ($this->hasIndex('analytics_page_views', 'analytics_page_views_type_id_viewed_at_idx')) {
            Schema::table('analytics_page_views', function (Blueprint $table) {
                $table->dropIndex('analytics_page_views_type_id_viewed_at_idx');
            });
        }

        if ($this->hasIndex('analytics_page_views', 'analytics_page_views_ip_address_idx')) {
            Schema::table('analytics_page_views', function (Blueprint $table) {
                $table->dropIndex('analytics_page_views_ip_address_idx');
            });
        }

        if ($this->hasIndex('local_info_entries', 'local_info_entries_type_active_idx')) {
            Schema::table('local_info_entries', function (Blueprint $table) {
                $table->dropIndex('local_info_entries_type_active_idx');
            });
        }

        if ($this->hasIndex('advertisements', 'advertisements_active_position_idx')) {
            Schema::table('advertisements', function (Blueprint $table) {
                $table->dropIndex('advertisements_active_position_idx');
            });
        }
    }
};
