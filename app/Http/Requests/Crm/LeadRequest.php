<?php

namespace App\Http\Requests\Crm;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lead_stage_id' => ['required', 'exists:lead_stages,id'],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'estimated_value' => ['required', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'owner_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'lost_reason' => ['nullable', 'string', 'max:255', 'required_if:status,lost'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'lead_stage_id' => 'stage',
            'company_name' => 'nama perusahaan',
            'contact_name' => 'nama kontak',
            'estimated_value' => 'estimasi nilai',
            'lost_reason' => 'alasan gagal',
        ];
    }
}
