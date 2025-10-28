<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('🔐 Recuperar Contraseña - ServiME')
            ->greeting('¡Hola!')
            ->line('Recibiste este email porque solicitaste recuperar tu contraseña en ServiME.')
            ->action('🔄 Recuperar Contraseña', $url)
            ->line('Este enlace expirará en 60 minutos.')
            ->line('Si no solicitaste este cambio, puedes ignorar este email.')
            ->salutation('¡Saludos!<br>El equipo de ServiME')
            ->markdown('emails.password-reset', [
                'url' => $url,
                'user' => $notifiable,
            ]);
    }
}
