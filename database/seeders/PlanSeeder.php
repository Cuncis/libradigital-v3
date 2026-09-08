<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Tiers and pricing per plan.md's confirmed defaults. Yearly is priced
     * at roughly 10x monthly (~2 months free), the standard SaaS discount.
     */
    public function run(): void
    {
        $tiers = [
            'starter' => [
                'label' => 'Starter',
                'monthly' => 49_000,
                'yearly' => 490_000,
                'invitation_limit' => 1,
                'features' => [
                    'remove_branding' => false,
                    'custom_domain' => false,
                    'rsvp_limit' => 50,
                    'guest_personalization' => false,
                    'priority_support' => false,
                ],
            ],
            'plus' => [
                'label' => 'Plus',
                'monthly' => 99_000,
                'yearly' => 990_000,
                'invitation_limit' => 3,
                'features' => [
                    'remove_branding' => true,
                    'custom_domain' => false,
                    'rsvp_limit' => 300,
                    'guest_personalization' => true,
                    'priority_support' => false,
                ],
            ],
            'pro' => [
                'label' => 'Pro',
                'monthly' => 199_000,
                'yearly' => 1_990_000,
                'invitation_limit' => 10,
                'features' => [
                    'remove_branding' => true,
                    'custom_domain' => true,
                    'rsvp_limit' => null,
                    'guest_personalization' => true,
                    'priority_support' => true,
                ],
            ],
            'organizer' => [
                'label' => 'Organizer',
                'monthly' => 499_000,
                'yearly' => 4_990_000,
                'invitation_limit' => null,
                'features' => [
                    'remove_branding' => true,
                    'custom_domain' => true,
                    'rsvp_limit' => null,
                    'guest_personalization' => true,
                    'priority_support' => true,
                    'white_label' => true,
                ],
            ],
        ];

        foreach ($tiers as $tier => $config) {
            foreach (['monthly', 'yearly'] as $interval) {
                Plan::query()->updateOrCreate(
                    ['tier' => $tier, 'billing_interval' => $interval],
                    [
                        'name' => "{$config['label']} ".ucfirst($interval),
                        'price' => $config[$interval],
                        'currency' => 'IDR',
                        'invitation_limit' => $config['invitation_limit'],
                        'features' => $config['features'],
                    ]
                );
            }
        }
    }
}
