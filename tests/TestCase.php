<?php

namespace Tests;

use App\Enums\ServiceType;
use App\Models\ServicePackage;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function makeServicePackage(
        ServiceType $type = ServiceType::Brand,
        string $service = 'Social Media Management',
        string $package = 'Silver',
    ): ServicePackage {
        return ServicePackage::query()
            ->where('name', $package)
            ->whereRelation('service', fn ($query) => $query
                ->where('type', $type)
                ->where('name', $service))
            ->firstOrFail();
    }
}
