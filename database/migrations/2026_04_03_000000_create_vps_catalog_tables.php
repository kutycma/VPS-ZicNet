<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vps_plan_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('fas fa-server');
            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });

        Schema::create('vps_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('default_group_id')->nullable()->constrained('vps_plan_groups')->nullOnDelete();
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

            $table->index(['status', 'markup_type']);
        });

        Schema::create('vps_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('vps_providers')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('vps_plan_groups')->nullOnDelete();
            $table->string('provider_plan_id')->nullable();
            $table->string('name');
            $table->enum('type', ['vps_vn', 'vps_nn'])->default('vps_vn');
            $table->unsignedInteger('cpu_cores')->default(0);
            $table->unsignedInteger('ram_mb')->default(0);
            $table->unsignedInteger('disk_gb')->default(0);
            $table->unsignedInteger('bandwidth_mbps')->default(0);
            $table->decimal('provider_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->json('pricing_data')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('provider_data')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['provider_id', 'provider_plan_id']);
            $table->index(['type', 'status', 'sort_order']);
            $table->index(['group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vps_plans');
        Schema::dropIfExists('vps_providers');
        Schema::dropIfExists('vps_plan_groups');
    }
};
