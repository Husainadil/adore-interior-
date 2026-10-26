<?php

namespace App\Policies;

use App\Models\CalculatorEstimate;
use App\Models\User;

class CalculatorEstimatePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, CalculatorEstimate $estimate): bool
    {
        return (bool) $user->is_admin;
    }

    public function manage(User $user): bool
    {
        return (bool) $user->is_admin;
    }
}
