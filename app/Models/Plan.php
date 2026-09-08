<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'tier',
        'billing_interval',
        'price',
        'currency',
        'invitation_limit',
        'features',
        'mayar_tier_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'invitation_limit' => 'integer',
            'features' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Plain-language bullets for the stored `features` flags, for pricing displays.
     *
     * @return array<int, string>
     */
    public function featureBullets(): array
    {
        $features = $this->features ?? [];
        $bullets = [];

        $bullets[] = match (true) {
            ! array_key_exists('rsvp_limit', $features) => null,
            $features['rsvp_limit'] === null => 'Unlimited RSVPs',
            default => "Up to {$features['rsvp_limit']} RSVPs",
        };

        $bullets[] = ($features['remove_branding'] ?? false) ? 'No Libra Digital branding' : null;
        $bullets[] = ($features['custom_domain'] ?? false) ? 'Custom domain support' : null;
        $bullets[] = ($features['guest_personalization'] ?? false) ? 'Personalized guest greetings' : null;
        $bullets[] = ($features['priority_support'] ?? false) ? 'Priority support' : null;
        $bullets[] = ($features['white_label'] ?? false) ? 'White-label for your clients' : null;

        return array_values(array_filter($bullets));
    }
}
