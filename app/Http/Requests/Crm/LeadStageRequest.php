<?php

namespace App\Http\Requests\Crm;

use App\Enums\LeadStageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadStageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'type' => ['required', Rule::enum(LeadStageType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kolom',
            'color' => 'warna',
            'type' => 'jenis kolom',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'color.regex' => 'Warna harus berupa kode heksadesimal, contoh #38bdf8.',
        ];
    }
}
