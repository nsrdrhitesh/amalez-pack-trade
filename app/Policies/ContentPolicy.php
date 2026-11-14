<?php

namespace App\Policies;

use App\Models\User;

class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, $record): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, $record): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, $record): bool
    {
        return $user->is_admin;
    }
}
