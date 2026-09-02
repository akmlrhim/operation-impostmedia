<?php

namespace App\Http\Requests\Crm;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
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
                Rule::unique('invoices', 'number')->ignore($this->route('invoice')),
            ],
            'client_id' => ['required', 'exists:clients,id'],
            'contract_id' => ['nullable', 'required_if:type,recurring', 'exists:contracts,id'],
            'type' => ['required', Rule::enum(InvoiceType::class)],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::enum(InvoiceStatus::class)],
            'notes' => ['nullable', 'string'],

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
            'number' => 'nomor invoice',
            'client_id' => 'klien',
            'contract_id' => 'MoU terkait',
            'issue_date' => 'tanggal invoice',
            'due_date' => 'jatuh tempo',
            'items' => 'item tagihan',
            'items.*.name' => 'uraian',
            'items.*.quantity' => 'volume',
            'items.*.unit_price' => 'harga satuan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contract_id.required_if' => 'Invoice retainer harus menyebut MoU yang ditagih.',
        ];
    }
}
