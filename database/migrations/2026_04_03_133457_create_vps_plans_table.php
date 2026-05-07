<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVpsPlansTable extends Migration
{
    public function up()
    {
        Schema::create('vps_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('vps_providers')->onDelete('cascade');
            $table->string('provider_plan_id')->nullable()->comment('ID trên API của NCC');
            $table->string('name');
            $table->enum('type', ['vps_vn', 'vps_nn'])->default('vps_vn');
            $table->integer('cpu_cores')->default(0);
            $table->integer('ram_mb')->default(0);
            $table->integer('disk_gb')->default(0);
            $table->integer('bandwidth_mbps')->default(0);
            $table->decimal('provider_price', 15, 2)->default(0)->comment('Giá vốn từ NCC');
            $table->decimal('selling_price', 15, 2)->default(0)->comment('Giá bán ra');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vps_plans');
    }
}
