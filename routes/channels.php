<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('crm', fn (User $user): bool => (bool) $user->is_active);
