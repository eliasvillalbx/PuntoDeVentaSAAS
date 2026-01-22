<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $email,
        public string $password,
    ) {}

    public function build()
    {
        return $this->subject('Tus credenciales de acceso — POS Empresarial')
            ->view('emails.user-credentials');
    }
}
