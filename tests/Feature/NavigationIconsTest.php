<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomRequests\CustomRequestResource;
use App\Filament\Resources\Invitations\InvitationResource as AdminInvitationResource;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Plans\PlanResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Resources\Themes\ThemeResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\User\Pages\Billing;
use App\Filament\User\Pages\Rsvps;
use App\Filament\User\Resources\Invitations\InvitationResource as UserInvitationResource;
use Filament\Facades\Filament;
use Tests\TestCase;

/**
 * Regression: every resource in both panels was left at the
 * make:filament-resource scaffold default (Heroicon::OutlinedRectangleStack)
 * — the whole sidebar showed the same icon for everything. Locks in that
 * every entry within a panel's sidebar has its own icon, so a newly
 * scaffolded resource can't silently reintroduce that by omission.
 */
class NavigationIconsTest extends TestCase
{
    public function test_admin_panel_sidebar_entries_have_distinct_icons(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertIconsAreDistinct([
            AdminInvitationResource::class,
            ThemeResource::class,
            CustomRequestResource::class,
            PlanResource::class,
            SubscriptionResource::class,
            UserResource::class,
            MediaResource::class,
        ]);
    }

    public function test_user_panel_sidebar_entries_have_distinct_icons(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('user'));

        $this->assertIconsAreDistinct([
            UserInvitationResource::class,
            MediaResource::class,
            Billing::class,
            Rsvps::class,
        ]);
    }

    /**
     * @param  array<class-string>  $classes
     */
    protected function assertIconsAreDistinct(array $classes): void
    {
        $icons = [];

        foreach ($classes as $class) {
            $icon = $class::getNavigationIcon();
            $key = $icon instanceof \BackedEnum ? $icon->value : (string) $icon;

            $existing = $icons[$key] ?? null;

            $this->assertNotNull($icon, "{$class} has no navigation icon set.");
            $this->assertArrayNotHasKey(
                $key,
                $icons,
                "{$class} uses the same navigation icon ({$key}) as {$existing}.",
            );

            $icons[$key] = $class;
        }
    }
}
