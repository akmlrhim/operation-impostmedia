<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CompanySettingRequest;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingController extends Controller
{
    public function edit(): Response
    {
        $company = CompanySetting::current();

        return Inertia::render('settings/company', [
            'company' => $company,
            'logoUrl' => $company->logoUrl(),
            'signatureUrl' => $company->signatureUrl(),
            'stampUrl' => $company->stampUrl(),
        ]);
    }

    public function update(CompanySettingRequest $request): RedirectResponse
    {
        $company = CompanySetting::current();
        $data = $request->validated();

        $company->update([
            ...collect($data)->except([
                'logo', 'signature', 'stamp',
                'remove_logo', 'remove_signature', 'remove_stamp',
            ])->all(),
            'logo_path' => $company->replaceImage(
                'logo_path',
                $request->file('logo'),
                (bool) ($data['remove_logo'] ?? false),
            ),
            'signature_path' => $company->replaceImage(
                'signature_path',
                $request->file('signature'),
                (bool) ($data['remove_signature'] ?? false),
            ),
            'stamp_path' => $company->replaceImage(
                'stamp_path',
                $request->file('stamp'),
                (bool) ($data['remove_stamp'] ?? false),
            ),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profil perusahaan diperbarui.']);

        return back();
    }
}
