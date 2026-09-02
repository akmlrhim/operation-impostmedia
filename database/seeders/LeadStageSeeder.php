<?php

namespace Database\Seeders;

use App\Enums\LeadStageType;
use App\Models\LeadStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LeadStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['name' => 'Lead', 'color' => '#64748b', 'type' => LeadStageType::Open],
            ['name' => 'Qualify', 'color' => '#0ea5e9', 'type' => LeadStageType::Open],
            ['name' => 'Contact', 'color' => '#6366f1', 'type' => LeadStageType::Open],
            ['name' => 'Proposal', 'color' => '#a855f7', 'type' => LeadStageType::Open],
            ['name' => 'Negosiasi', 'color' => '#f59e0b', 'type' => LeadStageType::Open],
            ['name' => 'Closed Won', 'color' => '#22c55e', 'type' => LeadStageType::Won],
            ['name' => 'Lost Deal', 'color' => '#ef4444', 'type' => LeadStageType::Lost],
        ];

        foreach ($stages as $position => $stage) {
            LeadStage::query()->updateOrCreate(
                ['slug' => Str::slug($stage['name'])],
                [
                    'name' => $stage['name'],
                    'color' => $stage['color'],
                    'type' => $stage['type'],
                    'position' => $position,
                ],
            );
        }
    }
}
