<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class ListPage
{
    public const PER_PAGE = 15;

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return LengthAwarePaginator<int, TModel>
     */
    public static function of(Builder $query, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $page = $query->paginate($perPage)->withQueryString();

        if ($page->currentPage() <= $page->lastPage()) {
            return $page;
        }

        return $query->paginate($perPage, page: $page->lastPage())->withQueryString();
    }
}
