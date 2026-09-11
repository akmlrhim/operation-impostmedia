<?php

namespace App\Http\Controllers;

use App\Support\Crm\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function read(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return back();
        }

        /** @var DatabaseNotification|null $row */
        $row = $user->notifications()->whereKey($notification)->first();

        if ($row === null) {
            return back();
        }

        if ($row->read_at === null) {
            $row->markAsRead();
        }

        $to = $this->destination($request, $row);

        return $to === '/' ? back() : redirect($to);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()?->unreadNotifications->markAsRead();

        return back();
    }

    private function destination(Request $request, DatabaseNotification $row): string
    {
        $stored = is_string($row->data['url'] ?? null) ? $row->data['url'] : '';

        if ($stored !== '') {
            return Notifier::path($stored);
        }

        return Notifier::path($request->string('to')->toString());
    }
}
