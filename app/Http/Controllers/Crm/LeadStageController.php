<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\LeadStageRequest;
use App\Models\LeadStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LeadStageController extends Controller
{
    public function store(LeadStageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        LeadStage::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name']),
            'position' => (int) LeadStage::query()->max('position') + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kolom ditambahkan.']);

        return to_route('leads.index');
    }

    public function update(LeadStageRequest $request, LeadStage $leadStage): RedirectResponse
    {
        $leadStage->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kolom diperbarui.']);

        return to_route('leads.index');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:lead_stages,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['ids'] as $position => $id) {
                LeadStage::query()->whereKey($id)->update(['position' => $position]);
            }
        });

        return back();
    }

    public function destroy(LeadStage $leadStage): RedirectResponse
    {
        if (LeadStage::query()->count() <= 1) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Papan harus punya minimal satu kolom.',
            ]);

            return back();
        }

        $leadStage->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kolom dihapus.']);

        return back();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'kolom';
        $slug = $base;
        $suffix = 2;

        while (LeadStage::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
