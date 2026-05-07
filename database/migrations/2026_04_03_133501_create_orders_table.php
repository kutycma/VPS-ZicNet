<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vps_plan_id')->constrained('vps_plans')->onDelete('cascade');
            $table->foreignId('vps_instance_id')->nullable()->constrained('vps_instances')->onDelete('set null');
            $table->decimal('amount', 15, 2);
            $table->string('billing_cycle')->nullable()->comment('Thời hạn thuê từ API');
            $table->integer('duration_months')->default(1);
            $table->enum('type', ['new', 'renew', 'upgrade'])->default('new');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('notes')->nullable();
            $table->json('api_response')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
