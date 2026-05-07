<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('group')->default('general');
                $table->timestamps();

                $table->index('group');
            });
        }

        if (!Schema::hasTable('themes')) {
            Schema::create('themes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(false);
                $table->boolean('is_default')->default(false);
                $table->json('variables');
                $table->text('custom_css')->nullable();
                $table->string('preview_image')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'is_default']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
        Schema::dropIfExists('settings');
    }
};
