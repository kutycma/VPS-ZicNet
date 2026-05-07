<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiUrlToPaymentMethodsAndDepositRequests extends Migration
{
    public function up()
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->text('api_url')->nullable()->after('secret_key')->comment('API URL kiểm tra lịch sử GD');
        });

        Schema::table('deposit_requests', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('user_id')->constrained('payment_methods')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('deposit_requests', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('api_url');
        });
    }
}
