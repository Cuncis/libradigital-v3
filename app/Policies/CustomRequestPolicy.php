<?php

namespace App\Policies;

use App\Models\CustomRequest;
use App\Models\User;
use App\UserRole;

class CustomRequestPolicy
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
    public function view(User $user, CustomRequest $customRequest): bool
    {
        return $this->ownsOrStaff($user, $customRequest);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CustomRequest $customRequest): bool
    {
        return $this->ownsOrStaff($user, $customRequest);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomRequest $customRequest): bool
    {
        return $user->role === UserRole::Admin || $user->id === $customRequest->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CustomRequest $customRequest): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CustomRequest $customRequest): bool
    {
        return $user->role === UserRole::Admin;
    }

    protected function ownsOrStaff(User $user, CustomRequest $customRequest): bool
    {
        return $user->role === UserRole::Admin || $user->id === $customRequest->user_id;
    }
}
