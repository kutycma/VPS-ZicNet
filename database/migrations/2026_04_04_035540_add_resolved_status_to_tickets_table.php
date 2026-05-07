<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResolvedStatusToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'answered', 'client-reply', 'closed', 'resolved') DEFAULT 'open'");
    }

    public function down()
    {
        // Reverting enum values is generally omitted or complex, 
        // but we can set it back if needed, assuming no 'resolved' tickets exist:
        // DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'answered', 'client-reply', 'closed') DEFAULT 'open'");
    }
}
