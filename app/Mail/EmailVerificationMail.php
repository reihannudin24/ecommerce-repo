<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $verificationToken;

    public function __construct($verificationToken)
    {
        $this->verificationToken = $verificationToken;
    }

    public function build()
    {
        return $this->subject('Verify Your Email Address')
            ->view('emails.verify_email') // Create a Blade view for the email template
            ->with(['verificationToken' => $this->verificationToken]);
    }
}
