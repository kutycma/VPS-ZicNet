<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->enum('type', ['deposit', 'purchase', 'refund', 'renewal', 'admin_adjust', 'promotion'])->default('deposit');
            $table->string('description');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            $table->string('reference')->nullable()->comment('Mã tham chiếu');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
