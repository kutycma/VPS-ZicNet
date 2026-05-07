<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVpsProvidersTable extends Migration
{
    public function up()
    {
        Schema::create('vps_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('api_endpoint');
            $table->string('api_username')->nullable();
            $table->string('api_app')->nullable();
            $table->string('api_secret')->nullable();
            $table->text('auth_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->enum('markup_type', ['percentage', 'fixed', 'manual'])->default('percentage');
            $table->decimal('markup_value', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vps_providers');
    }
}
