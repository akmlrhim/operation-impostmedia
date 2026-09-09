<?php

namespace App\Http\Requests\Crm;

use App\Enums\ClientStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('short_code')) {
            $this->merge([
                'short_code' => Str::upper(trim((string) $this->input('short_code'))) ?: null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'short_code' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('clients', 'short_code')->ignore($this->route('client')),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_position' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ClientStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'nama perusahaan',
            'short_code' => 'kode singkat',
            'contact_name' => 'nama PIC',
        ];
    }
}
