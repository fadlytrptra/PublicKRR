<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OTPMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $user;
    public $otp;
    public $activity;
    public function __construct($user, $otp, $activity)
    {
        $this->user = $user;
        $this->otp = $otp;
        $this->activity = $activity;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        $subjects = [
            'Registrasi User' => 'OTP Registrasi Kerta Rajasa Raya',
            'Approval Surat Jalan' => 'OTP Product Receipt Kerta Rajasa Raya',
            'Approval Pasca Kirim' => 'OTP Pasca Kirim Kerta Rajasa Raya',
        ];

        return new Envelope(
            subject: $subjects[$this->activity] ?? 'OTP Kerta Rajasa Raya',
        );
    }

    public function build()
    {
        return $this->view('emails.OTP');
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.OTP',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
