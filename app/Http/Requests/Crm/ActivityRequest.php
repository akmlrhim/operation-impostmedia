<?php

namespace App\Http\Requests\Crm;

use App\Enums\ActivityType;
use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ActivityType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'scheduled_at' => ['nullable', 'date'],
            'completed' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(?Activity $activity = null): array
    {
        $data = $this->validated();

        $completedAt = null;

        if ($this->boolean('completed')) {
            $completedAt = $activity !== null && $activity->completed_at !== null
                ? $activity->completed_at
                : now();
        }

        return [
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'completed_at' => $completedAt,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'jenis catatan',
            'title' => 'judul',
            'description' => 'detail',
            'scheduled_at' => 'waktu',
        ];
    }
}
