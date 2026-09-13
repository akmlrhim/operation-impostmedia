<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;

abstract class Controller
{
    /**
     * Member dipagari ke data miliknya sendiri; yang lain 404 supaya
     * keberadaan data milik orang lain tidak bocor lewat rute.
     */
    public function ensureVisible(Lead|Client|Contract|Invoice $model): void
    {
        abort_unless($model->isAccessibleBy(auth()->user()), 404);
    }
}
