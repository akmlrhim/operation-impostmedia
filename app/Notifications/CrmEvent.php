<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CrmEvent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $kind,
        private readonly string $title,
        private readonly string $subtitle,
        private readonly string $url,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title.' - '.config('company.name'))
            ->markdown('emails.crm.event', [
                'kind' => $this->kind,
                'title' => $this->title,
                'subtitle' => $this->subtitle,
                'url' => $this->url,
            ]);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'url' => $this->url,
        ];
    }
}
