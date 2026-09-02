<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ScopePointsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'service_package_id' => ['nullable', 'integer', 'exists:service_packages,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'uraian pekerjaan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Isi dulu uraian pekerjaannya, itu yang jadi bahan tulisan AI.',
        ];
    }
}
