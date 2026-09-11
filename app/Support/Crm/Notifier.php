<?php

namespace App\Support\Crm;

use App\Enums\UserRole;
use App\Events\CrmChanged;
use App\Models\User;
use App\Notifications\CrmEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class Notifier
{
    public const TOPIC = 'notifications';

    public const RECENT = 10;

    /**
     * Kabari penanggung jawab dan pembuat sebuah data, kecuali orang yang
     * melakukan perubahannya sendiri.
     */
    public static function involved(Model $record, string $kind, string $title, string $subtitle, string $url): void
    {
        /** @var list<int> $ids */
        $ids = method_exists($record, 'involvedUserIds') ? $record->involvedUserIds() : [];

        self::to($ids, $kind, $title, $subtitle, $url);
    }

    public static function superusers(string $kind, string $title, string $subtitle, string $url): void
    {
        $ids = User::query()
            ->where('role', UserRole::Superuser)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        self::to(array_values($ids), $kind, $title, $subtitle, $url);
    }

    /**
     * @param  list<int>  $userIds
     */
    public static function to(array $userIds, string $kind, string $title, string $subtitle, string $url): void
    {
        $actorId = auth()->id();

        $recipients = User::query()
            ->whereIn('id', $userIds)
            ->when($actorId !== null, fn ($query) => $query->whereKeyNot($actorId))
            ->where('is_active', true)
            ->whereNotNull('approved_at')
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new CrmEvent($kind, $title, $subtitle, self::path($url)));

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        broadcast(new CrmChanged(self::TOPIC, 'created'));
    }

    /**
     * Tujuan notifikasi selalu disimpan sebagai alamat di dalam aplikasi ini,
     * supaya sekali klik langsung mendarat di datanya dan tidak bisa dibelokkan
     * ke alamat luar.
     */
    public static function path(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return '/';
        }

        $path = (string) ($parts['path'] ?? '');

        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '/';
        }

        $query = (string) ($parts['query'] ?? '');
        $fragment = (string) ($parts['fragment'] ?? '');

        return $path
            .($query === '' ? '' : '?'.$query)
            .($fragment === '' ? '' : '#'.$fragment);
    }
}
