<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVpsInstancesTable extends Migration
{
    public function up()
    {
        Schema::create('vps_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('vps_plans')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('vps_providers')->onDelete('cascade');
            $table->string('vps_provider_id')->nullable()->comment('ID từ API của NCC');
            $table->string('ip_address')->nullable();
            $table->string('os')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('hostname')->nullable();
            $table->enum('status', ['pending', 'progressing', 'active', 'stopped', 'expired', 'cancelled'])->default('pending');
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at_provider')->nullable();
            $table->json('provider_data')->nullable()->comment('Raw data from provider API');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vps_instances');
    }
}
