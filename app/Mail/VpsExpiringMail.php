<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\VpsInstance;

class VpsExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public $vps;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(VpsInstance $vps)
    {
        $this->vps = $vps;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Sắp hết hạn Dịch vụ VPS #' . ($this->vps->vps_provider_id ?? $this->vps->id) . ' - VPS ZicNet')
                    ->view('emails.vps_expiring');
    }
}
