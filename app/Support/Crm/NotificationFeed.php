<?php

namespace App\Support\Crm;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationFeed
{
    /**
     * @return array{unread: int, items: list<array{id: string, kind: string, title: string, subtitle: string, url: string, at: string|null, read: bool}>}
     */
    public static function props(?User $user): array
    {
        if ($user === null) {
            return ['unread' => 0, 'items' => []];
        }

        $items = $user->notifications()
            ->limit(Notifier::RECENT)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => [
                'id' => (string) $notification->id,
                'kind' => (string) ($notification->data['kind'] ?? 'info'),
                'title' => (string) ($notification->data['title'] ?? ''),
                'subtitle' => (string) ($notification->data['subtitle'] ?? ''),
                'url' => Notifier::path((string) ($notification->data['url'] ?? '/')),
                'at' => $notification->created_at?->toIso8601String(),
                'read' => $notification->read_at !== null,
            ])
            ->all();

        return [
            'unread' => $user->unreadNotifications()->count(),
            'items' => array_values($items),
        ];
    }
}
