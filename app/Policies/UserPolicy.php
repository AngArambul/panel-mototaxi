<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $account): bool
    {
        return $this->viewAny($user) && ! $user->is($account) && in_array($account->rol, User::MANAGED_ROLES, true);
    }
}
