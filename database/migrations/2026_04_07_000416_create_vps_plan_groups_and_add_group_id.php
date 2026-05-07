<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bảng nhóm VPS Plan
        Schema::create('vps_plan_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // "VPS Giá Rẻ", "VPS Premium"
            $table->string('slug')->unique();
            $table->string('icon')->default('fas fa-server'); // FontAwesome icon
            $table->integer('sort_order')->default(0);
            $table->string('status')->default('active'); // active/inactive
            $table->timestamps();
        });

        // 2. Thêm group_id vào vps_plans
        Schema::table('vps_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('provider_id');
            $table->foreign('group_id')->references('id')->on('vps_plan_groups')->onDelete('set null');
        });

        // 3. Thêm default_group_id vào vps_providers
        Schema::table('vps_providers', function (Blueprint $table) {
            $table->unsignedBigInteger('default_group_id')->nullable()->after('description');
            $table->foreign('default_group_id')->references('id')->on('vps_plan_groups')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('vps_providers', function (Blueprint $table) {
            $table->dropForeign(['default_group_id']);
            $table->dropColumn('default_group_id');
        });

        Schema::table('vps_plans', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::dropIfExists('vps_plan_groups');
    }
};
