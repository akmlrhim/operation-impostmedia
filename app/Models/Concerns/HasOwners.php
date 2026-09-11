<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\Crm\Notifier;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasOwners
{
    /**
     * Cara data ini disebut di daftar notifikasi.
     *
     * @return array{subject: string, label: string, url: string}
     */
    abstract public function notificationMeta(): array;

    protected static function bootHasOwners(): void
    {
        static::created(function (self $model): void {
            $model->notifyNewAssignees();
        });
    }

    public function notifyNewAssignees(): void
    {
        $userIds = $this->assignees()->pluck('users.id')->all();

        if ($userIds === []) {
            return;
        }

        $meta = $this->notificationMeta();

        Notifier::to(
            $userIds,
            'assigned',
            $meta['label'],
            $meta['subject'].' ditugaskan kepada Anda',
            $meta['url'],
        );
    }

    /** @return MorphToMany<User, $this> */
    public function assignees(): MorphToMany
    {
        return $this->morphToMany(User::class, 'assignable', 'crm_assignees')
            ->withTimestamps();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Penanggung jawab dan pembuat, dipakai untuk menentukan siapa yang
     * dikabari saat data ini berubah.
     *
     * @return list<int>
     */
    public function involvedUserIds(): array
    {
        $assigneeIds = $this->assignees()->pluck('users.id')->all();
        $creatorId = $this->getAttribute('created_by');

        $ids = array_merge($assigneeIds, $creatorId !== null ? [$creatorId] : []);

        return array_values(array_unique(array_map(
            fn (mixed $id): int => (int) $id,
            $ids,
        )));
    }
}
