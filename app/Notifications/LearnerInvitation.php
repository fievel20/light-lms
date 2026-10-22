<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class LearnerInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Number of days the activation link stays valid.
     */
    public const int VALID_FOR_DAYS = 7;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'invitation.show',
            now()->addDays(self::VALID_FOR_DAYS),
            ['user' => $notifiable->id],
        );

        return (new MailMessage)
            ->subject('Ton accès à '.config('app.name'))
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Ton formateur t'a inscrit(e) sur ".config('app.name').' pour suivre et valider tes compétences.')
            ->line('Pour activer ton compte, choisis ton mot de passe :')
            ->action('Activer mon compte', $url)
            ->line('Ce lien est valable '.self::VALID_FOR_DAYS." jours. S'il a expiré, demande à ton formateur de te renvoyer une invitation.");
    }
}
