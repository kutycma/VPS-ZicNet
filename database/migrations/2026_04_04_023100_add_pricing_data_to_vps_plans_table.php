<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPricingDataToVpsPlansTable extends Migration
{
    public function up()
    {
        Schema::table('vps_plans', function (Blueprint $table) {
            $table->json('pricing_data')->nullable()->after('selling_price')->comment('All billing cycle prices from provider');
        });
    }

    public function down()
    {
        Schema::table('vps_plans', function (Blueprint $table) {
            $table->dropColumn('pricing_data');
        });
    }
}
