<?php

namespace App\Http\Requests\Crm;

use App\Enums\BillingCycle;
use App\Enums\ContractStatus;
use App\Enums\ContractType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('contracts', 'number')->ignore($this->route('contract')),
            ],
            'client_id' => ['required', 'exists:clients,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'type' => ['required', Rule::enum(ContractType::class)],
            'title' => ['required', 'string', 'max:255'],
            'scope' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'signing_place' => ['nullable', 'string', 'max:100'],
            'signed_date' => ['required', 'date'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_terms' => ['nullable', 'string'],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
            'next_invoice_date' => ['nullable', 'date'],
            'first_party_name' => ['nullable', 'string', 'max:255'],
            'first_party_position' => ['nullable', 'string', 'max:255'],
            'second_party_name' => ['nullable', 'string', 'max:255'],
            'second_party_position' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ContractStatus::class)],

            'items' => ['required', 'array', 'min:1'],
            'items.*.service_package_id' => ['nullable', 'exists:service_packages,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'number' => 'nomor MoU',
            'client_id' => 'klien',
            'title' => 'judul',
            'signed_date' => 'tanggal MoU',
            'items' => 'ruang lingkup pekerjaan',
            'items.*.name' => 'uraian pekerjaan',
            'items.*.quantity' => 'volume',
            'items.*.unit_price' => 'harga satuan',
        ];
    }
}
