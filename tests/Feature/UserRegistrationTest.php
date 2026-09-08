<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Filament\Auth\Pages\Register;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('user'));
    }

    public function test_self_registration_creates_a_customer_who_can_access_the_user_panel(): void
    {
        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'Amara',
                'email' => 'amara@example.com',
                'password' => 'password123',
                'passwordConfirmation' => 'password123',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'amara@example.com')->sole();

        // Not passed anywhere in the registration form — this is exactly
        // the gap that caused the Phase 6 bug (role null in-memory right
        // after create() until the next request re-hydrates from the DB).
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('user')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }
}
