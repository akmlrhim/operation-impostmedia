<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Support\Crm\DashboardAttention;
use App\Support\Crm\DashboardStats;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $monthStart = now()->startOfMonth();

        return Inertia::render('crm/dashboard', [
            'company' => CompanySetting::current()->name,
            'period' => DashboardStats::MONTH_LABELS[(int) $monthStart->month].' '.$monthStart->year,
            'stats' => DashboardStats::stats($monthStart),
            'trend' => DashboardStats::trend($monthStart),
            'aging' => DashboardAttention::aging(),
            'attention' => DashboardAttention::attention(),
            'activities' => DashboardAttention::activities(),
        ]);
    }
}
