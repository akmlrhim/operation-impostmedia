<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PendingApprovalController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isApproved()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('pending-approval', [
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }
}
