<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('gateway')->nullable();
                $table->string('account_name')->nullable();
                $table->string('account_number')->nullable();
                $table->string('bank_name')->nullable();
                $table->text('api_key')->nullable();
                $table->text('secret_key')->nullable();
                $table->text('api_url')->nullable();
                $table->text('instructions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        }

        if (!Schema::hasTable('deposit_requests')) {
            Schema::create('deposit_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
                $table->string('transaction_code')->unique();
                $table->decimal('amount', 15, 2);
                $table->decimal('actual_amount', 15, 2)->nullable();
                $table->enum('status', ['pending', 'completed', 'expired', 'cancelled', 'rejected'])->default('pending');
                $table->string('bank_transaction_id')->nullable()->unique();
                $table->text('bank_description')->nullable();
                $table->timestamp('matched_at')->nullable();
                $table->timestamp('expires_at');
                $table->string('admin_note')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['status', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_requests');
        Schema::dropIfExists('payment_methods');
    }
};
