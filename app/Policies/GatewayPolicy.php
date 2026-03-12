<?php

namespace App\Policies;

use App\Models\User;

class GatewayPolicy
{
    public function manage(User $user): bool
    {
        return $user->role === 'ADMIN';
    }
}
