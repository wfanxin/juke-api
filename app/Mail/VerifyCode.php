<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyCode extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * 验证码
     * @var string
     */
    public $vCode = "";

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($vCode)
    {
        //
        $this->vCode = $vCode;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        //
        return $this->view('emails.auth.verifyCode');
    }
}
