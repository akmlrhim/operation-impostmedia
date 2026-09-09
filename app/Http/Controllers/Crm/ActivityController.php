<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ActivityRequest;
use App\Models\Activity;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ActivityController extends Controller
{
    public function store(ActivityRequest $request, Lead $lead): RedirectResponse
    {
        $lead->activities()->create([
            ...$request->payload(),
            'user_id' => auth()->id(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catatan ditambahkan.']);

        return back();
    }

    public function update(ActivityRequest $request, Activity $activity): RedirectResponse
    {
        $activity->update($request->payload($activity));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catatan diperbarui.']);

        return back();
    }

    public function toggle(Activity $activity): RedirectResponse
    {
        $activity->update([
            'completed_at' => $activity->completed_at === null ? now() : null,
        ]);

        return back();
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $activity->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catatan dihapus.']);

        return back();
    }
}
