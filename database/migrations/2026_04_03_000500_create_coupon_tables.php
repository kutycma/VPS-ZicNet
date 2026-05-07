<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('description')->nullable();
                $table->enum('type', ['fixed', 'percent'])->default('fixed');
                $table->decimal('value', 15, 2);
                $table->decimal('min_order_amount', 15, 2)->default(0);
                $table->decimal('max_discount', 15, 2)->nullable();
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('max_uses_per_user')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->foreignId('plan_id')->nullable()->constrained('vps_plans')->nullOnDelete();
                $table->foreignId('group_id')->nullable()->constrained('vps_plan_groups')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();

                $table->index(['status', 'starts_at', 'expires_at']);
                $table->index(['plan_id', 'group_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('coupon_usages')) {
            Schema::create('coupon_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->timestamps();

                $table->unique(['coupon_id', 'user_id', 'order_id']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
