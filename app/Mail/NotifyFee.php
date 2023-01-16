<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\contract\Mailer;
use Illuminate\Support\Facades\Mail;

use Illuminate\Queue\SerializesModels;

class NotifyFee extends Mailable
{
    use Queueable, SerializesModels;
    private $fee, $enddate;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($fee, $enddate)
    {
        //
        $this->fee = $fee;
        $this->enddate = $enddate;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $fee = $this->fee;
        $enddate = $this->enddate;
        return $this->subject('Notifikasi Membayar Yuran')
            ->view('emails.feeMail', compact('fee', 'enddate'));
    }
}
