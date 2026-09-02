<?php

use App\Http\Middleware\EnsureUserIsApproved;
use App\Http\Middleware\EnsureUserIsSuperuser;
use App\Http\Middleware\EnsureVisitorIsHuman;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'approved' => EnsureUserIsApproved::class,
            'superuser' => EnsureUserIsSuperuser::class,
            'turnstile' => EnsureVisitorIsHuman::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || ! $e->getPrevious() instanceof ModelNotFoundException) {
                return null;
            }

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Data yang dicari sudah tidak ada, mungkin sudah dihapus.',
            ]);

            return redirect()->back(fallback: route('dashboard'));
        });
    })->create();
