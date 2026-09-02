<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ListRedirect
{
    public static function to(string $routeName): RedirectResponse
    {
        $list = route($routeName);

        $previous = URL::previous();

        return redirect()->to(Str::before($previous, '?') === $list ? $previous : $list);
    }
}
