<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Batas per menit untuk endpoint yang mahal: `ai` memanggil Groq berbayar,
     * `documents` merender PDF lewat dompdf, `exports` dan `uploads` menyedot I/O.
     *
     * @var array<string, int>
     */
    private const RATE_LIMITS = [
        'ai' => 15,
        'documents' => 30,
        'exports' => 20,
        'uploads' => 30,
    ];

    public function register(): void {}

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSecurity();
        $this->configureRateLimits();
        $this->configurePerformance();
        $this->configureGates();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        CarbonImmutable::setLocale('id');

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureSecurity(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }

    protected function configureRateLimits(): void
    {
        foreach (self::RATE_LIMITS as $name => $perMinute) {
            RateLimiter::for($name, fn (Request $request): Limit => Limit::perMinute($perMinute)
                ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
        }
    }

    protected function configurePerformance(): void
    {
        Model::preventLazyLoading(app()->runningUnitTests());

        Vite::prefetch(concurrency: 3);
    }

    protected function configureGates(): void
    {
        Gate::before(fn (User $user): ?bool => $user->isSuperuser() ? true : null);

        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user): bool => in_array(
                $permission->value,
                RolePermission::grants($user->role),
                true,
            ));
        }
    }
}
