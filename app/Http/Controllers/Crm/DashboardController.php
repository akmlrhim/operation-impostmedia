<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Support\CompanyProfile;
use App\Support\Crm\DashboardAttention;
use App\Support\Crm\DashboardStats;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $monthStart = DashboardStats::resolveMonth($request->string('month')->toString());

        return Inertia::render('crm/dashboard', [
            'company' => CompanyProfile::name(),
            'month' => $monthStart->format('Y-m'),
            'months' => DashboardStats::months(),
            'period' => DashboardStats::monthLabel($monthStart),
            'stats' => DashboardStats::stats($monthStart),
            'trend' => DashboardStats::trend($monthStart),
            'temperature' => DashboardStats::temperature(),
            'aging' => DashboardAttention::aging(),
            'attention' => DashboardAttention::attention(),
            'activities' => DashboardAttention::activities($monthStart),
        ]);
    }
}
