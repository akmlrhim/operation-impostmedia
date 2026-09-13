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
        $this->ensureVisible($lead);

        $lead->activities()->create([
            ...$request->payload(),
            'user_id' => auth()->id(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catatan ditambahkan.']);

        return back();
    }

    public function update(ActivityRequest $request, Activity $activity): RedirectResponse
    {
        $this->ensureActivityAccessible($activity);

        $activity->update($request->payload($activity));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catatan diperbarui.']);

        return back();
    }

    public function toggle(Activity $activity): RedirectResponse
    {
        $this->ensureActivityAccessible($activity);

        $activity->update([
            'completed_at' => $activity->completed_at === null ? now() : null,
        ]);

        return back();
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->ensureActivityAccessible($activity);

        $activity->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catatan dihapus.']);

        return back();
    }

    private function ensureActivityAccessible(Activity $activity): void
    {
        $user = auth()->user();

        if ($user->isManagerOrAbove()) {
            return;
        }

        $subject = $activity->subject;

        $accessible = $activity->user_id === $user->id
            || ($subject !== null && method_exists($subject, 'isAccessibleBy') && $subject->isAccessibleBy($user));

        abort_unless($accessible, 404);
    }
}
