<?php

namespace App\Policies;

use App\Models\User;

class PrivateRecordPolicy extends ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin && in_array($user->cms_role, ['owner', 'manager'], true);
    }

    public function view(User $user, $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, $record): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, $record): bool
    {
        return $user->is_admin && $user->cms_role === 'owner';
    }
}
