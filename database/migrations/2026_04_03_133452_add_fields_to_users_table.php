<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'user'])->default('user')->after('password');
            $table->decimal('balance', 15, 2)->default(0)->after('role');
            $table->enum('status', ['active', 'suspended', 'banned'])->default('active')->after('balance');
            $table->string('phone', 20)->nullable()->after('email');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'balance', 'status', 'phone']);
        });
    }
}
