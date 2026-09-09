<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CompanySettingRequest;
use App\Models\CompanySetting;
use App\Support\CompanyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanySettingController extends Controller
{
    public function edit(): Response
    {
        $company = CompanySetting::current();

        return Inertia::render('settings/company', [
            'profile' => CompanyProfile::identity(),
            'logoUrl' => CompanyProfile::logoUrl(),
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
                'signature', 'stamp',
                'remove_signature', 'remove_stamp',
            ])->all(),
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

    public function image(string $kind): StreamedResponse
    {
        $path = CompanySetting::current()->imagePath($kind);
        $disk = Storage::disk(CompanySetting::DISK);

        abort_if($path === null || ! $disk->exists($path), 404);

        return $disk->response($path, headers: [
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }
}
