<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoCloseTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:autoclose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically close tickets that have been in resolved state for 24 hours';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cutoff = now()->subHours(24);
        
        $tickets = \App\Models\Ticket::where('status', 'resolved')
            ->where('updated_at', '<=', $cutoff)
            ->get();
            
        foreach ($tickets as $ticket) {
            $ticket->update(['status' => 'closed', 'updated_at' => now()]);
            \App\Models\TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => \App\Models\User::where('is_admin', true)->first()->id ?? 1, // fallback to an admin
                'message' => 'Hệ thống: Ticket đã được tự động đóng vì không nhận được phản hồi sau 24 giờ kể từ khi báo hoàn thành.'
            ]);
        }
        
        $this->info("Auto closed {$tickets->count()} tickets.");
        
        return 0;
    }
}
