<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepositRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('deposit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('transaction_code')->unique()->comment('Mã unique: ZICNET + counter');
            $table->decimal('amount', 15, 2)->comment('Số tiền yêu cầu nạp');
            $table->decimal('actual_amount', 15, 2)->nullable()->comment('Số tiền thực nhận từ bank');
            $table->enum('status', ['pending', 'completed', 'expired', 'cancelled', 'rejected'])->default('pending');
            $table->string('bank_transaction_id')->nullable()->unique()->comment('transactionNumber từ bank');
            $table->text('bank_description')->nullable()->comment('Nội dung CK gốc từ bank');
            $table->timestamp('matched_at')->nullable()->comment('Thời điểm khớp GD');
            $table->timestamp('expires_at')->comment('Thời điểm hết hạn');
            $table->string('admin_note')->nullable()->comment('Ghi chú admin khi duyệt thủ công');
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index('transaction_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deposit_requests');
    }
}
