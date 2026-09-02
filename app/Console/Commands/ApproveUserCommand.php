<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;

class ApproveUserCommand extends Command
{
    protected $signature = 'user:approve
                            {email : Alamat email pendaftar yang disetujui}
                            {--role= : superuser, administrator, manager, atau member}';

    protected $description = 'Setujui pendaftar Google dan tetapkan perannya';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->components->error("Tidak ada user dengan alamat {$email}.");
            $this->components->info('Pendaftar baru muncul setelah orangnya sekali masuk lewat tombol Google.');

            return self::FAILURE;
        }

        $role = $this->option('role') ?? select(
            label: 'Peran',
            options: array_column(UserRole::cases(), 'value'),
            default: $user->role->value,
        );

        $parsedRole = UserRole::tryFrom((string) $role);

        if ($parsedRole === null) {
            $this->components->error("Peran [{$role}] tidak dikenal.");

            return self::FAILURE;
        }

        $alreadyApproved = $user->isApproved();

        $user->forceFill([
            'role' => $parsedRole,
            'approved_at' => $user->approved_at ?? now(),
            'is_active' => true,
        ])->save();

        $this->components->info($alreadyApproved
            ? "Peran {$email} diubah jadi {$parsedRole->label()}."
            : "{$email} disetujui sebagai {$parsedRole->label()}.");

        return self::SUCCESS;
    }
}
