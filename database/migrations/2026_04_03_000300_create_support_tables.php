<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('subject');
                $table->enum('department', ['technical', 'sales'])->default('technical');
                $table->enum('status', ['open', 'answered', 'client-reply', 'closed', 'resolved'])->default('open');
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['department', 'priority', 'status']);
            });
        }

        if (!Schema::hasTable('ticket_messages')) {
            Schema::create('ticket_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('message');
                $table->timestamps();

                $table->index(['ticket_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
    }
};
