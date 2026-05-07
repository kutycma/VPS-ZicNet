<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderDataToVpsPlansTable extends Migration
{
    public function up()
    {
        Schema::table('vps_plans', function (Blueprint $table) {
            $table->json('provider_data')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('vps_plans', function (Blueprint $table) {
            $table->dropColumn('provider_data');
        });
    }
}
