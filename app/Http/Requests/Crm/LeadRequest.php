<?php

namespace App\Http\Requests\Crm;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (blank($this->input('date_in'))) {
            $this->merge(['date_in' => now()->toDateString()]);
        }

        if (blank($this->input('temperature'))) {
            $this->merge(['temperature' => LeadTemperature::Cold->value]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lead_stage_id' => ['required', 'exists:lead_stages,id'],
            'date_in' => ['required', 'date'],
            'company_name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'vacancy_position' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'region' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'pic' => ['nullable', 'string', 'max:255'],
            'service_package_ids' => ['array'],
            'service_package_ids.*' => ['integer', 'distinct', 'exists:service_packages,id'],
            'estimated_value' => ['required', 'numeric', 'min:0'],
            'last_contact_date' => ['nullable', 'date'],
            'next_action_date' => ['nullable', 'date'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'meeting_date' => ['nullable', 'date'],
            'temperature' => ['required', Rule::enum(LeadTemperature::class)],
            'notes' => ['nullable', 'string'],
            'folder_url' => ['nullable', 'url', 'max:2048'],
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'lost_reason' => ['nullable', 'string', 'max:255', 'required_if:status,lost'],
            'assigned_to_ids' => ['nullable', 'array'],
            'assigned_to_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'lead_stage_id' => 'stage',
            'date_in' => 'tanggal masuk',
            'company_name' => 'nama klien',
            'industry' => 'industri',
            'vacancy_position' => 'posisi loker',
            'contact_name' => 'nama kontak',
            'region' => 'asal daerah',
            'service_package_ids' => 'layanan dibutuhkan',
            'service_package_ids.*' => 'layanan dibutuhkan',
            'estimated_value' => 'estimasi nilai',
            'last_contact_date' => 'kontak terakhir',
            'next_action_date' => 'tanggal aksi berikutnya',
            'next_action' => 'aksi berikutnya',
            'meeting_date' => 'tanggal meeting',
            'temperature' => 'temperature',
            'folder_url' => 'link folder',
            'lost_reason' => 'alasan gagal',
            'assigned_to_ids' => 'penanggung jawab',
            'assigned_to_ids.*' => 'penanggung jawab',
        ];
    }
}
