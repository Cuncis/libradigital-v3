<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use App\UserRole;

class InvitationPolicy
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
    public function view(User $user, Invitation $invitation): bool
    {
        return $this->ownsOrAdmin($user, $invitation);
    }

    /**
     * Determine whether the user can create models. Admins always can (they
     * build invitations on customers' behalf for custom requests); customers
     * are gated by their plan's invitation limit — see canCreateInvitation().
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->canCreateInvitation();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invitation $invitation): bool
    {
        return $this->ownsOrAdmin($user, $invitation);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        return $this->ownsOrAdmin($user, $invitation);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invitation $invitation): bool
    {
        return $this->ownsOrAdmin($user, $invitation);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invitation $invitation): bool
    {
        return $user->role === UserRole::Admin;
    }

    protected function ownsOrAdmin(User $user, Invitation $invitation): bool
    {
        return $user->role === UserRole::Admin || $user->id === $invitation->user_id;
    }
}
