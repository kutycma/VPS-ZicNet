<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpirationNotifiedAtToVpsInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            $table->timestamp('expiration_notified_at')->nullable()->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vps_instances', function (Blueprint $table) {
            $table->dropColumn('expiration_notified_at');
        });
    }
}
