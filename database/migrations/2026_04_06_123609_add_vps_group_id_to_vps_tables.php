<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVpsGroupIdToVpsTables extends Migration
{
    public function up()
    {
        Schema::table('vps_providers', function (Blueprint $table) {
            $table->unsignedBigInteger('vps_group_id')->nullable()->after('id');
            $table->foreign('vps_group_id')->references('id')->on('vps_groups')->nullOnDelete();
        });

        Schema::table('vps_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('vps_group_id')->nullable()->after('provider_id');
            $table->foreign('vps_group_id')->references('id')->on('vps_groups')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('vps_providers', function (Blueprint $table) {
            $table->dropForeign(['vps_group_id']);
            $table->dropColumn('vps_group_id');
        });

        Schema::table('vps_plans', function (Blueprint $table) {
            $table->dropForeign(['vps_group_id']);
            $table->dropColumn('vps_group_id');
        });
    }
}
