<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\SubscriptionStatus;
use App\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * In-memory default matching the `role` column's DB default. Without
     * this, a freshly `create()`d model (e.g. Filament's own registration
     * flow, which never submits `role`) has a null `role` attribute in
     * memory until the next request re-fetches it from the DB — meaning
     * canAccessPanel() would wrongly deny a customer immediately after they
     * register, on that same request.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'customer',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->role === UserRole::Admin,
            'user' => $this->role === UserRole::Customer,
            default => false,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function customRequests(): HasMany
    {
        return $this->hasMany(CustomRequest::class);
    }

    public function assignedCustomRequests(): HasMany
    {
        return $this->hasMany(CustomRequest::class, 'assigned_admin_id');
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->latest('current_period_end')
            ->first();
    }

    public function currentPlan(): ?Plan
    {
        return $this->activeSubscription()?->plan;
    }

    /**
     * Whether the user's current plan allows creating another invitation.
     * False with no active subscription — there's no free tier.
     */
    public function canCreateInvitation(): bool
    {
        $plan = $this->currentPlan();

        if (! $plan) {
            return false;
        }

        if ($plan->invitation_limit === null) {
            return true;
        }

        return $this->invitations()->count() < $plan->invitation_limit;
    }
}
