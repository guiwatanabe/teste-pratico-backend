<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        return true;
    }

    public function refund(User $user, Transaction $transaction): bool
    {
        return in_array($user->role, ['ADMIN', 'FINANCE']);
    }
}
