<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe — Boursiv')
            ->greeting('Bonjour !')
            ->line('Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.')
            ->action('Réinitialiser le mot de passe', $url)
            ->line('Ce lien expirera dans ' . config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60) . ' minutes.')
            ->line('Si vous n\'avez pas demandé de réinitialisation, aucune action n\'est requise.')
            ->salutation('Cordialement, l\'équipe Boursiv');
    }
}
