<?php

namespace App\Filament\User\Pages;

use App\Models\Plan;
use App\Models\Subscription;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class Billing extends Page
{
    protected string $view = 'filament.user.pages.billing';

    #[Computed]
    public function activeSubscription(): ?Subscription
    {
        return auth()->user()->activeSubscription();
    }

    /**
     * @return Collection<string, Collection<int, Plan>>
     */
    #[Computed]
    public function plansByTier(): Collection
    {
        return Plan::query()->orderBy('price')->get()->groupBy('tier');
    }
}
