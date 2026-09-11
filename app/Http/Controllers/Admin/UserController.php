<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Support\EnumOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $superusers = User::query()->where('role', UserRole::Superuser)->count();

        $users = User::query()
            ->orderByRaw('approved_at is null desc')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
                'approved_at' => $user->approved_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'is_self' => $user->is($request->user()),
                'is_last_superuser' => $user->role === UserRole::Superuser && $superusers === 1,
            ]);

        return Inertia::render('users/index', [
            'users' => $users,
            'roles' => EnumOptions::from(UserRole::class),
        ]);
    }

    public function approve(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($user->isApproved()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'User ini sudah disetujui.']);

            return back();
        }

        $user->forceFill([
            'role' => $request->role(),
            'approved_at' => now(),
            'is_active' => true,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$user->name} disetujui sebagai {$request->role()->label()}.",
        ]);

        return back();
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($this->wouldLockOutSelf($request, $user)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Anda tidak bisa mencabut wewenang superuser Anda sendiri.',
            ]);

            return back();
        }

        $user->forceFill([
            'role' => $request->role(),
            'is_active' => $request->boolean('is_active'),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User diperbarui.']);

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Anda tidak bisa menghapus akun Anda sendiri dari sini.',
            ]);

            return back();
        }

        if ($this->isLastSuperuser($user)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Superuser terakhir tidak bisa dihapus. Angkat superuser lain dulu.',
            ]);

            return back();
        }

        $name = $user->name;
        $wasWaiting = ! $user->isApproved();

        $user->notifications()->delete();
        $user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $wasWaiting
                ? "Pendaftaran {$name} ditolak."
                : "Akun {$name} dihapus. Lead, MoU, dan invoicenya tetap tersimpan.",
        ]);

        return back();
    }

    private function isLastSuperuser(User $user): bool
    {
        if ($user->role !== UserRole::Superuser) {
            return false;
        }

        return User::query()
            ->where('role', UserRole::Superuser)
            ->whereKeyNot($user->getKey())
            ->doesntExist();
    }

    private function wouldLockOutSelf(UpdateUserRequest $request, User $user): bool
    {
        if (! $user->is($request->user())) {
            return false;
        }

        return $request->role() !== UserRole::Superuser || ! $request->boolean('is_active');
    }
}
