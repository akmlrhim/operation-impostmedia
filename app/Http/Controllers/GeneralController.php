<?php

namespace App\Http\Controllers;

use App\Support\CompanyProfile;
use Inertia\Inertia;
use Inertia\Response;

class GeneralController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('general', [
            'company' => CompanyProfile::name(),
        ]);
    }
}
