<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_slug')->index();
            $table->string('new_slug');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->integer('status_code')->default(301);
            $table->timestamps();

            $table->unique(['old_slug', 'model_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
