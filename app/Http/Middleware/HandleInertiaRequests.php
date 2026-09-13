<?php

namespace App\Http\Middleware;

use App\Support\Crm\NotificationFeed;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user()
                    ?->loadMissing('googleAccount')
                    ->append('avatar')
                    ->makeHidden('googleAccount'),
            ],
            'can' => [
                'manage-users' => $request->user()?->can('manage-users') ?? false,
                'manage-master-data' => $request->user()?->can('manage-master-data') ?? false,
                'manage-finance' => $request->user()?->can('manage-finance') ?? false,
                'approve-documents' => $request->user()?->can('approve-documents') ?? false,
                'manage-records' => $request->user()?->can('manage-records') ?? false,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'notifications' => fn (): array => NotificationFeed::props($request->user()),
        ];
    }
}
