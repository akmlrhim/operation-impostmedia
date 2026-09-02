<?php

namespace App\Http\Requests\Crm;

use App\Enums\ServiceBillingType;
use App\Enums\ServiceType;
use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ServiceType::class)],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('services', 'name')
                    ->where('type', $this->input('type'))
                    ->ignore($this->currentService()?->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],

            'packages' => ['required', 'array', 'min:1'],
            'packages.*.id' => ['nullable', 'integer'],
            'packages.*.name' => ['required', 'string', 'max:255', 'distinct'],
            'packages.*.description' => ['nullable', 'string'],
            'packages.*.price' => ['required', 'numeric', 'min:0'],
            'packages.*.unit' => ['required', 'string', 'max:50'],
            'packages.*.billing_type' => ['required', Rule::enum(ServiceBillingType::class)],
            'packages.*.is_active' => ['boolean'],
            'packages.*.requires_visit' => ['boolean'],

            'packages.*.points' => ['array'],
            'packages.*.points.*.label' => ['required', 'string', 'max:255'],
        ];
    }

    private function currentService(): ?Service
    {
        $service = $this->route('service');

        return $service instanceof Service ? $service : null;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'tipe layanan',
            'name' => 'nama layanan',
            'packages' => 'paket',
            'packages.*.name' => 'nama paket',
            'packages.*.price' => 'harga paket',
            'packages.*.unit' => 'satuan paket',
            'packages.*.points.*.label' => 'poin paket',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'packages.required' => 'Layanan harus punya minimal satu paket.',
            'packages.min' => 'Layanan harus punya minimal satu paket.',
            'packages.*.name.distinct' => 'Nama paket tidak boleh sama dalam satu layanan.',
        ];
    }
}
