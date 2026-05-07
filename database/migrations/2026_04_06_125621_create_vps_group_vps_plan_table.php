<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVpsGroupVpsPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vps_group_vps_plan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vps_group_id');
            $table->unsignedBigInteger('vps_plan_id');
            $table->timestamps();

            $table->foreign('vps_group_id')->references('id')->on('vps_groups')->onDelete('cascade');
            $table->foreign('vps_plan_id')->references('id')->on('vps_plans')->onDelete('cascade');
            
            $table->unique(['vps_group_id', 'vps_plan_id']);
        });

        // Drop the old column from vps_plans
        Schema::table('vps_plans', function (Blueprint $table) {
            if (Schema::hasColumn('vps_plans', 'vps_group_id')) {
                $table->dropForeign(['vps_group_id']);
                $table->dropColumn('vps_group_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vps_group_vps_plan');
        
        Schema::table('vps_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('vps_plans', 'vps_group_id')) {
                $table->unsignedBigInteger('vps_group_id')->nullable()->after('provider_id');
                $table->foreign('vps_group_id')->references('id')->on('vps_groups')->onDelete('set null');
            }
        });
    }
}
