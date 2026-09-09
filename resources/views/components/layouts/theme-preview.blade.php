<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/invitation.css', 'resources/js/invitation.js'])
    </head>
    <body class="min-h-screen bg-white font-sans text-gray-900 antialiased">
        <div class="bg-blue-50 px-4 py-2 text-center text-sm font-medium text-blue-800">
            Theme preview — this is the starting point an invitation gets when created from this theme.
        </div>

        {{ $slot }}
    </body>
</html>
