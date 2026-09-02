<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateUserCommand extends Command
{
    protected $signature = 'user:create
                            {email? : Alamat email akun Google-nya}
                            {--name= : Nama tampilan}
                            {--role= : superuser, administrator, manager, atau member}';

    protected $description = 'Daftarkan user baru supaya bisa masuk lewat akun Google';

    public function handle(): int
    {
        $email = $this->argument('email') ?? text(
            label: 'Alamat email akun Google',
            required: true,
        );

        $name = $this->option('name') ?? text(
            label: 'Nama tampilan',
            required: true,
        );

        $role = $this->option('role') ?? select(
            label: 'Peran',
            options: array_column(UserRole::cases(), 'value'),
            default: UserRole::default()->value,
        );

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'role' => $role],
            [
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'name' => ['required', 'string', 'max:255'],
                'role' => ['required', 'string'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $parsedRole = UserRole::tryFrom((string) $role);

        if ($parsedRole === null) {
            $this->components->error("Peran [{$role}] tidak dikenal.");

            return self::FAILURE;
        }

        $user = new User;

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'role' => $parsedRole,
            'is_active' => true,
            'approved_at' => now(),
        ])->save();

        $this->components->info("User {$email} terdaftar sebagai {$parsedRole->label()}.");
        $this->components->info('Silakan masuk lewat tombol Google di halaman login.');

        return self::SUCCESS;
    }
}
