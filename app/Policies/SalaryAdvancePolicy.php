<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SalaryAdvance;

class SalaryAdvancePolicy
{
    public function approve(User $user, SalaryAdvance $advance)
    {
        return $user->hasRole('hr') || $user->hasRole('director') || $user->hasRole('admin');
    }
}
