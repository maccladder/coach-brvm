<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PackBrvmConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
        public Collection $courses,
    ) {}

    public function build(): static
    {
        return $this
            ->subject('🎓 Votre Pack BRVM Complet est activé !')
            ->view('emails.pack_brvm_confirmation');
    }
}
