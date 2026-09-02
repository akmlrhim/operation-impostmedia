<?php

namespace App\Models\Concerns;

use App\Events\CrmChanged;
use Illuminate\Support\Str;

trait BroadcastsCrmChanges
{
    protected static function bootBroadcastsCrmChanges(): void
    {
        foreach (['created', 'updated', 'deleted', 'restored'] as $action) {
            static::registerModelEvent($action, function (self $model) use ($action): void {
                $model->broadcastCrmChange($action);
            });
        }
    }

    public function crmBroadcastTopic(): string
    {
        return Str::plural(Str::kebab(class_basename($this)));
    }

    public function broadcastCrmChange(string $action): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        broadcast(new CrmChanged($this->crmBroadcastTopic(), $action, $this->getKey()))->toOthers();
    }
}
