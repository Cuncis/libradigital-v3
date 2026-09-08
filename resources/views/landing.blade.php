<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }} — Digital invitations for your event</title>
        <meta name="description" content="Build a wedding, birthday, or event invitation with your own photos, schedule, and RSVPs — or tell us what you want and we'll design it for you.">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ config('app.name') }} — Digital invitations for your event">
        <meta property="og:description" content="Build a wedding, birthday, or event invitation with your own photos, schedule, and RSVPs — or tell us what you want and we'll design it for you.">
        <meta property="og:url" content="{{ url('/') }}">
        <meta name="twitter:card" content="summary">
        <link rel="canonical" href="{{ url('/') }}">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-paper text-ink font-sans antialiased">
        <header class="border-b border-line">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                <span class="font-display text-lg text-ink">{{ config('app.name') }}</span>
                <a href="{{ route('filament.user.auth.login') }}" class="text-sm text-ink-muted hover:text-ink">
                    Sign in
                </a>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="overflow-hidden">
                <div class="mx-auto grid max-w-6xl items-center gap-16 px-6 py-20 md:grid-cols-2 md:py-28">
                    <div>
                        <h1 data-hero-line class="font-display text-4xl leading-[1.15] text-ink sm:text-5xl">
                            Send an invitation people actually open.
                        </h1>
                        <p data-hero-line class="mt-6 max-w-md text-lg text-ink-muted">
                            Build a wedding, birthday, or event invitation with your own photos, schedule, and RSVPs.
                            Prefer not to? Tell us what you have in mind and we'll design it for you.
                        </p>
                        <div data-hero-line class="mt-8 flex flex-wrap items-center gap-6">
                            <a
                                href="{{ route('filament.user.auth.register') }}"
                                class="inline-flex items-center rounded-sm bg-pine px-6 py-3 text-sm font-medium text-paper hover:bg-pine-deep"
                            >
                                Start your invitation
                            </a>
                            <a href="#custom-request" class="text-sm font-medium text-ink hover:text-pine">
                                Or have us design it
                            </a>
                        </div>
                    </div>

                    <div class="flex justify-center md:justify-end">
                        <div
                            data-hero-card
                            class="w-full max-w-sm rotate-2 rounded-sm border border-line bg-paper-deep p-10 shadow-[0_20px_45px_-25px_rgba(36,32,26,0.35)]"
                        >
                            <p class="text-xs tracking-normal text-ink-muted">Together with their families</p>
                            <p class="mt-4 font-display text-3xl text-ink">Amara &amp; Reyhan</p>
                            <div class="mt-6 h-px w-12 bg-brass"></div>
                            <p class="mt-6 text-sm text-ink-muted">Saturday, the fourteenth of June</p>
                            <p class="text-sm text-ink-muted">Bandung, West Java</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section class="border-t border-line py-20">
                <div class="mx-auto max-w-6xl px-6">
                    <h2 class="font-display text-3xl text-ink">How it works</h2>

                    <div class="mt-12 grid gap-10 md:grid-cols-3">
                        <div class="border-t border-brass pt-4">
                            <p class="font-display text-lg text-ink">1. Choose a theme</p>
                            <p class="mt-2 text-sm text-ink-muted">
                                Start from a wedding, birthday, or event layout, or a blank canvas.
                            </p>
                        </div>
                        <div class="border-t border-brass pt-4">
                            <p class="font-display text-lg text-ink">2. Make it yours</p>
                            <p class="mt-2 text-sm text-ink-muted">
                                Add your photos, schedule, and venue, then preview it on any screen size.
                            </p>
                        </div>
                        <div class="border-t border-brass pt-4">
                            <p class="font-display text-lg text-ink">3. Share the link</p>
                            <p class="mt-2 text-sm text-ink-muted">
                                Guests open it on their phone and RSVP straight from the page.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Theme gallery --}}
            @if ($themes->isNotEmpty())
                <section id="themes" class="border-t border-line bg-paper-deep/40 py-20">
                    <div class="mx-auto max-w-6xl px-6">
                        <h2 class="font-display text-3xl text-ink">Start from a theme</h2>
                        <p class="mt-3 max-w-md text-sm text-ink-muted">
                            Pick one to begin. Everything — colors, photos, wording — can be changed after.
                        </p>

                        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($themes as $theme)
                                <a
                                    href="{{ route('filament.user.auth.register') }}"
                                    class="block rounded-sm border border-line bg-paper p-6 hover:border-brass"
                                >
                                    @if ($theme->category)
                                        <p class="text-xs text-ink-muted capitalize">{{ $theme->category }}</p>
                                    @endif
                                    <p class="mt-2 font-display text-xl text-ink">{{ $theme->name }}</p>
                                    @if ($theme->description)
                                        <p class="mt-2 text-sm text-ink-muted">{{ $theme->description }}</p>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            {{-- Pricing --}}
            <section id="pricing" class="border-t border-line py-20">
                <div class="mx-auto max-w-6xl px-6">
                    <h2 class="font-display text-3xl text-ink">Pricing</h2>
                    <p class="mt-3 max-w-md text-sm text-ink-muted">
                        Billed monthly or yearly, in Rupiah. Every plan includes the visual editor.
                    </p>

                    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($plansByTier as $tier => $plans)
                            @php
                                $monthly = $plans->firstWhere('billing_interval', 'monthly');
                                $yearly = $plans->firstWhere('billing_interval', 'yearly');
                            @endphp

                            <div class="flex flex-col rounded-sm border border-line bg-paper p-6">
                                <p class="font-display text-xl capitalize text-ink">{{ $tier }}</p>

                                @if ($monthly)
                                    <p class="mt-4 text-2xl text-ink">
                                        Rp {{ number_format($monthly->price, 0, ',', '.') }}
                                        <span class="text-sm font-normal text-ink-muted">/mo</span>
                                    </p>
                                @endif

                                @if ($yearly)
                                    <p class="mt-1 text-xs text-ink-muted">
                                        or Rp {{ number_format($yearly->price, 0, ',', '.') }} billed yearly
                                    </p>
                                @endif

                                <ul class="mt-6 flex-1 space-y-2 text-sm text-ink-muted">
                                    @foreach (($monthly ?? $yearly)->featureBullets() as $bullet)
                                        <li>{{ $bullet }}</li>
                                    @endforeach
                                </ul>

                                <a
                                    href="{{ route('filament.user.auth.register') }}"
                                    class="mt-6 inline-flex items-center justify-center rounded-sm border border-ink px-4 py-2 text-sm font-medium text-ink hover:border-pine hover:text-pine"
                                >
                                    Sign up to subscribe
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Custom request --}}
            <section id="custom-request" class="border-t border-line bg-paper-deep/40 py-20">
                <div class="mx-auto grid max-w-6xl gap-12 px-6 md:grid-cols-2">
                    <div>
                        <h2 class="font-display text-3xl text-ink">Prefer we build it for you?</h2>
                        <p class="mt-4 max-w-md text-sm text-ink-muted">
                            Tell us about your event and what you have in mind. We'll get back to you with a design
                            and a quote.
                        </p>
                    </div>

                    <div>
                        @if (session('custom_request_submitted'))
                            <div class="rounded-sm border border-pine bg-paper p-6 text-sm text-ink">
                                Thanks — we've received your request and will be in touch shortly.
                            </div>
                        @else
                            <form method="POST" action="{{ route('custom-requests.store') }}" class="space-y-4">
                                @csrf

                                @unless (auth()->check())
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="name" class="text-sm text-ink">Your name</label>
                                            <input id="name" name="name" type="text" required value="{{ old('name') }}"
                                                class="mt-1 w-full rounded-sm border-line bg-paper text-sm" />
                                        </div>
                                        <div>
                                            <label for="email" class="text-sm text-ink">Email</label>
                                            <input id="email" name="email" type="email" required value="{{ old('email') }}"
                                                class="mt-1 w-full rounded-sm border-line bg-paper text-sm" />
                                        </div>
                                    </div>
                                    <div>
                                        <label for="phone" class="text-sm text-ink">Phone (optional)</label>
                                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}"
                                            class="mt-1 w-full rounded-sm border-line bg-paper text-sm" />
                                    </div>
                                @endunless

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="event_type" class="text-sm text-ink">Event type</label>
                                        <select id="event_type" name="event_type" required
                                            class="mt-1 w-full rounded-sm border-line bg-paper text-sm">
                                            <option value="wedding">Wedding</option>
                                            <option value="birthday">Birthday</option>
                                            <option value="corporate">Corporate</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="event_date" class="text-sm text-ink">Event date</label>
                                        <input id="event_date" name="event_date" type="date" value="{{ old('event_date') }}"
                                            class="mt-1 w-full rounded-sm border-line bg-paper text-sm" />
                                    </div>
                                </div>

                                <div>
                                    <label for="budget" class="text-sm text-ink">Budget (IDR, optional)</label>
                                    <input id="budget" name="budget" type="number" min="0" value="{{ old('budget') }}"
                                        class="mt-1 w-full rounded-sm border-line bg-paper text-sm" />
                                </div>

                                <div>
                                    <label for="style_notes" class="text-sm text-ink">What do you have in mind?</label>
                                    <textarea id="style_notes" name="style_notes" rows="4"
                                        class="mt-1 w-full rounded-sm border-line bg-paper text-sm">{{ old('style_notes') }}</textarea>
                                </div>

                                @if ($errors->any())
                                    <div class="text-sm text-red-700">
                                        <ul class="list-inside list-disc">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <button type="submit"
                                    class="inline-flex items-center rounded-sm bg-pine px-6 py-3 text-sm font-medium text-paper hover:bg-pine-deep">
                                    Request a custom design
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-line py-10">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 text-sm text-ink-muted">
                <span>{{ config('app.name') }} &copy; {{ now()->year }}</span>
                <div class="flex gap-6">
                    <a href="#themes" class="hover:text-ink">Themes</a>
                    <a href="#pricing" class="hover:text-ink">Pricing</a>
                    <a href="{{ route('filament.user.auth.login') }}" class="hover:text-ink">Sign in</a>
                </div>
            </div>
        </footer>
    </body>
</html>
