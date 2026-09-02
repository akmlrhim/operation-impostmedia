<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Inertia\Inertia;
use Inertia\Response;

class GeneralController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('general', [
            'company' => CompanySetting::current()->name,
        ]);
    }
}
