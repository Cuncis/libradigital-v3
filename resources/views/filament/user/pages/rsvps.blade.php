<x-filament-panels::page>
    <div class="rounded-xl border border-gray-200 p-6 dark:border-gray-700">
        <p class="text-sm text-gray-600 dark:text-gray-400">Guests attending, across all your invitations</p>
        <p class="mt-1 text-2xl font-semibold">{{ $this->attendingCount }}</p>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
