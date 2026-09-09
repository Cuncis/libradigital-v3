{{--
    A standalone, full-screen editor layout — no sidebar, no topbar — for
    the invitation builder, the same way Elementor's own editor takes over
    the whole browser window instead of living inside wp-admin's chrome.

    Reuses Filament's own <x-filament-panels::layout.base> (the shared
    <html>/<head> shell: CSRF, dark mode, Livewire/Alpine/Filament assets,
    notifications) rather than hand-rolling any of that, so this adds no
    extra weight of its own — it only *removes* the topbar and sidebar
    Livewire components the default layout renders. The page's own
    getHeaderActions() (Preview, Save changes, Delete, ...) still render
    normally as part of the page content below, and the standard
    breadcrumbs give a way back to the resource's list page.
--}}
@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="fi-main-ctn">
        <main id="fi-main-content" tabindex="-1" class="fi-main">
            {{ $slot }}
        </main>
    </div>
</x-filament-panels::layout.base>
