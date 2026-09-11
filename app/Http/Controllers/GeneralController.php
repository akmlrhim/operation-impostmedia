<?php

namespace App\Http\Controllers;

use App\Support\CompanyProfile;
use App\Support\Crm\AgendaCalendar;
use App\Support\Crm\DashboardStats;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneralController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $month = AgendaCalendar::resolveMonth($request->string('month')->toString());

        /** @var list<string> $requested */
        $requested = array_values(array_filter(
            explode(',', $request->string('types')->toString()),
            fn (string $type): bool => in_array($type, AgendaCalendar::TYPES, true),
        ));

        $types = $requested === [] ? AgendaCalendar::TYPES : $requested;
        $mine = $request->boolean('mine');
        $view = $request->string('view')->toString() === 'list' ? 'list' : 'calendar';

        return Inertia::render('general', [
            'company' => CompanyProfile::name(),
            'today' => now()->toDateString(),
            'month' => $month->format('Y-m'),
            'period' => DashboardStats::monthLabel($month),
            'types' => $types,
            'mine' => $mine,
            'view' => $view,
            'events' => AgendaCalendar::events($month, $types, $mine ? $request->user()?->id : null),
        ]);
    }
}
