<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>
        <x-layup-seo />

        @vite(['resources/css/invitation.css', 'resources/js/invitation.js'])
    </head>
    <body class="min-h-screen bg-white font-sans text-gray-900 antialiased">
        @if (isset($layupPage) && $layupPage->status !== 'published')
            <div class="bg-amber-50 px-4 py-2 text-center text-sm font-medium text-amber-800">
                Preview — not published yet. Guests can't see this page.
            </div>
        @endif

        @if ($guestName ?? null)
            <div data-guest-banner class="bg-gray-50 px-4 py-3 text-center text-sm text-gray-600">
                Dear {{ $guestName }},
            </div>
        @endif

        {{ $slot }}
    </body>
</html>
