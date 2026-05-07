<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vps_instances')) {
            Schema::create('vps_instances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained('vps_plans')->nullOnDelete();
                $table->foreignId('provider_id')->constrained('vps_providers')->cascadeOnDelete();
                $table->string('vps_provider_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('os')->nullable();
                $table->string('username')->nullable();
                $table->string('password')->nullable();
                $table->string('hostname')->nullable();
                $table->enum('status', ['pending', 'progressing', 'active', 'stopped', 'expired', 'cancelled', 'deleted'])->default('pending');
                $table->boolean('auto_renew')->default(false);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('expiration_notified_at')->nullable();
                $table->timestamp('created_at_provider')->nullable();
                $table->json('provider_data')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['provider_id', 'vps_provider_id']);
                $table->index(['status', 'expires_at']);
            });
        }

        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vps_plan_id')->constrained('vps_plans')->cascadeOnDelete();
                $table->foreignId('vps_instance_id')->nullable()->constrained('vps_instances')->nullOnDelete();
                $table->decimal('amount', 15, 2);
                $table->string('billing_cycle')->nullable();
                $table->unsignedInteger('duration_months')->default(1);
                $table->enum('type', ['new', 'renew', 'upgrade'])->default('new');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
                $table->text('notes')->nullable();
                $table->json('api_response')->nullable();
                $table->json('payload')->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['type', 'status']);
                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('amount', 15, 2);
                $table->decimal('balance_before', 15, 2);
                $table->decimal('balance_after', 15, 2);
                $table->enum('type', ['deposit', 'purchase', 'refund', 'renewal', 'admin_adjust', 'promotion'])->default('deposit');
                $table->string('description');
                $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
                $table->string('reference')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['type', 'status']);
                $table->index('reference');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('vps_instances');
    }
};
