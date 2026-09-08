<x-filament-panels::page>
    @if ($errors->has('checkout'))
        <div class="rounded-lg bg-danger-50 p-4 text-sm text-danger-700 dark:bg-danger-950 dark:text-danger-300">
            {{ $errors->first('checkout') }}
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 p-6 dark:border-gray-700">
        <h2 class="text-lg font-semibold">Current plan</h2>

        @if ($this->activeSubscription)
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ $this->activeSubscription->plan->name }} &middot;
                renews {{ $this->activeSubscription->current_period_end?->format('d M Y') ?? '—' }}
            </p>
        @else
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                You don't have an active subscription yet. Pick a plan below to get started.
            </p>
        @endif

        <p class="mt-4 text-xs text-gray-500 dark:text-gray-500">
            Mayar doesn't provide a self-service billing portal link via API, so subscription
            changes go through Mayar's own checkout/payment pages rather than a "manage" link here.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($this->plansByTier as $tier => $plans)
            <div class="rounded-xl border border-gray-200 p-6 dark:border-gray-700">
                <h3 class="text-base font-semibold capitalize">{{ $tier }}</h3>

                @foreach ($plans as $plan)
                    <form method="POST" action="{{ route('subscribe', $plan) }}" class="mt-4">
                        @csrf

                        @if (! auth()->user()->phone)
                            <input
                                type="tel"
                                name="phone"
                                placeholder="Phone number"
                                required
                                class="mb-2 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900"
                            />
                        @endif

                        <button
                            type="submit"
                            class="w-full rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-500"
                        >
                            Rp {{ number_format($plan->price, 0, ',', '.') }} / {{ $plan->billing_interval }}
                        </button>
                    </form>
                @endforeach
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
