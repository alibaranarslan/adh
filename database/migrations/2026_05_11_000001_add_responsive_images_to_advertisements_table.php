<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->string('desktop_image_path', 500)->nullable()->after('image_path');
            $table->string('mobile_image_path', 500)->nullable()->after('desktop_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropColumn(['desktop_image_path', 'mobile_image_path']);
        });
    }
};
