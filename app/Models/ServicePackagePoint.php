<?php

namespace App\Models;

use App\Models\Concerns\BroadcastsCrmChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePackagePoint extends Model
{
    use BroadcastsCrmChanges;

    protected $guarded = ['id'];

    public function crmBroadcastTopic(): string
    {
        return 'services';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<ServicePackage, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'service_package_id');
    }
}
