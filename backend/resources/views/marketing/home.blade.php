<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitHub — The gym platform that opens the door too.</title>
    <meta name="description" content="FitHub is the gym operator dashboard with real smart-lock and fingerprint hardware built in — access turns off automatically when a membership lapses. Plus members, classes, attendance, and revenue in one board. 14-day free trial, no card required.">

    @if (file_exists(public_path('images/branding/logo.png')))
        <link rel="icon" type="image/png" href="{{ asset('images/branding/logo.png') }}">
    @endif

    @vite('resources/css/app.css')
</head>
<body class="font-sans bg-void text-ink">

    {{-- Nav --}}
    <header class="relative z-10 max-w-6xl mx-auto px-6 lg:px-8 py-6 flex items-center justify-between">
        <a href="/" class="flex items-center gap-3">
            @include('partials.logo', ['class' => 'w-8 h-8', 'textClass' => 'text-xs'])
            <span class="font-display font-semibold tracking-wide text-base">FITHUB</span>
        </a>
        <nav class="flex items-center gap-6 text-sm">
            <a href="#pricing" class="text-steel-2 hover:text-ink transition hidden sm:inline">Pricing</a>
            <a href="{{ route('login') }}" class="text-steel-2 hover:text-ink transition">Log in</a>
            <a href="/start-trial" class="fh-btn-primary !py-2 !px-4">Start free trial</a>
        </nav>
    </header>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="relative max-w-6xl mx-auto px-6 lg:px-8 pt-8 pb-20 lg:pt-12 lg:pb-28">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div class="motion-safe:animate-fade-up">
                    <p class="fh-eyebrow text-gold mb-4">Software + Real Door Hardware</p>
                    <h1 class="font-display font-semibold text-5xl lg:text-6xl leading-[1.05] tracking-tight text-balance mb-6">
                        The gym platform<br>that opens the door too.
                    </h1>
                    <p class="text-steel-2 text-base lg:text-lg leading-relaxed mb-8 max-w-md">
                        Members, classes, attendance, and door access — tracked live, in one board.
                    </p>
                    <div class="flex flex-wrap items-center gap-4 mb-4">
                        <a href="/start-trial" class="fh-btn-primary !py-3 !px-6">Start free 14-day trial</a>
                        <a href="#pricing" class="fh-btn-secondary !py-3 !px-6">See pricing</a>
                    </div>
                    <p class="text-xs text-steel font-mono">No card required</p>
                </div>

                <div class="relative motion-safe:animate-fade-up" style="animation-delay: 120ms">
                    <div class="absolute -inset-6 rounded-2xl bg-gradient-to-br from-gold/15 via-gold-2/10 to-transparent blur-2xl"></div>
                    @php
                        $heroFile = collect(['hero-login.webp', 'hero-login.jpg', 'hero-login.png'])
                            ->first(fn ($f) => file_exists(public_path('images/auth/'.$f)));
                    @endphp
                    <div class="relative rounded-2xl overflow-hidden border border-chalk-3 aspect-[3/4] shadow-2xl shadow-black/40">
                        @if ($heroFile)
                            <img src="{{ asset('images/auth/'.$heroFile) }}" alt="Member training on the gym floor" class="w-full h-full object-cover object-top">
                        @else
                            <div class="w-full h-full bg-chalk flex items-center justify-center">
                                <div class="w-24 h-24 rounded-full bg-gold/10"></div>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-void/70 via-transparent to-transparent"></div>
                        <div class="absolute left-5 right-5 bottom-5 flex items-center justify-between font-mono text-xs">
                            <span class="text-ink font-semibold">Door unlocked</span>
                            <span class="fh-pill-good">Live</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Hardware differentiator --}}
    <section class="relative border-t border-chalk-3 bg-chalk">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="fh-eyebrow text-gold mb-3">Not just another dashboard</p>
            <h2 class="fh-heading text-2xl lg:text-3xl mb-10 max-w-xl">Membership status controls the physical door — automatically.</h2>

            <div class="grid sm:grid-cols-2 gap-5">
                <div class="fh-card">
                    <p class="fh-eyebrow text-gold mb-2">Smart Lock</p>
                    <p class="font-display font-semibold text-lg mb-2">Unlock from the dashboard or the app.</p>
                    <p class="text-sm text-steel-2 leading-relaxed">Every open is logged automatically, in real time.</p>
                </div>
                <div class="fh-card">
                    <p class="fh-eyebrow text-gold mb-2">Fingerprint Check-in</p>
                    <p class="font-display font-semibold text-lg mb-2">One scan checks you in and unlocks the door.</p>
                    <p class="text-sm text-steel-2 leading-relaxed">No card, no fob, nothing for the front desk to hand out.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Feature strip --}}
    <section class="relative border-t border-chalk-3">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="fh-eyebrow text-gold mb-3">What's on the board</p>
            <h2 class="fh-heading text-2xl lg:text-3xl mb-10 max-w-lg">Everything the front desk touches.</h2>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ([
                    ['title' => 'Members & Plans', 'body' => 'Onboard members and track renewals before they lapse.'],
                    ['title' => 'QR Check-in', 'body' => 'Members scan in at the door — no sign-in sheets.'],
                    ['title' => 'Classes & Bookings', 'body' => 'Schedule classes and let members book their own spot.'],
                    ['title' => 'Billing & Payments', 'body' => 'Stripe subscriptions with invoices members can see.'],
                    ['title' => 'Staff Access', 'body' => 'Scoped logins for staff — no full-dashboard handover.'],
                    ['title' => 'Revenue Insight', 'body' => 'MRR, churn risk, and attendance trends, live.'],
                ] as $feature)
                    <div class="fh-card">
                        <p class="font-display font-semibold text-base mb-1.5">{{ $feature['title'] }}</p>
                        <p class="text-sm text-steel-2 leading-relaxed">{{ $feature['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="relative">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="fh-eyebrow text-gold mb-3">Pricing</p>
            <h2 class="fh-heading text-2xl lg:text-3xl mb-3">One plan for every stage of your gym.</h2>
            <p class="text-steel-2 text-sm mb-10">14-day free trial. No card required.</p>

            <div class="grid sm:grid-cols-3 gap-5">
                @forelse ($plans as $plan)
                    <div class="fh-card flex flex-col {{ $loop->iteration === 2 ? 'border-gold/40' : '' }}">
                        @if ($loop->iteration === 2)
                            <span class="fh-pill-good w-fit mb-3">Most popular</span>
                        @endif
                        <p class="text-lg font-display font-semibold">{{ $plan->name }}</p>
                        @if ($plan->description)
                            <p class="text-sm text-steel-2 mt-1">{{ $plan->description }}</p>
                        @endif

                        <div class="mt-5 space-y-1">
                            <p class="fh-td-mono text-2xl">${{ rtrim(rtrim($plan->monthly_price, '0'), '.') }}<span class="text-steel text-sm font-sans">/mo</span></p>
                            @if ($plan->yearly_price)
                                <p class="fh-td-mono text-steel text-xs">or ${{ rtrim(rtrim($plan->yearly_price, '0'), '.') }}/yr</p>
                            @endif
                        </div>

                        @if ($plan->features)
                            <ul class="text-sm text-steel-2 mt-6 space-y-2 flex-1">
                                @foreach ($plan->features as $feature)
                                    <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> {{ $feature }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div class="flex-1"></div>
                        @endif

                        <a href="/start-trial" class="fh-btn-primary w-full !py-2.5 mt-6 text-center">Start free trial</a>
                    </div>
                @empty
                    <p class="text-sm text-steel col-span-3">Pricing is being finalized — check back shortly, or contact us below.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- CTA + footer --}}
    <section class="relative border-t border-chalk-3 bg-chalk">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20 text-center">
            <h2 class="fh-heading text-2xl lg:text-3xl mb-4">Ready to get off the spreadsheet?</h2>
            <p class="text-steel-2 text-sm mb-8">No card required for the first 14 days.</p>
            <a href="/start-trial" class="fh-btn-primary !py-3 !px-8">Start free trial</a>
        </div>
    </section>

    <footer class="max-w-6xl mx-auto px-6 lg:px-8 py-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-steel">
        <p>&copy; {{ date('Y') }} FitHub &middot; built by RankSol</p>
        <div class="flex items-center gap-5">
            <a href="mailto:{{ config('app.support_email') }}" class="hover:text-ink transition">{{ config('app.support_email') }}</a>
            <a href="{{ route('legal.terms') }}" class="hover:text-ink transition">Terms</a>
            <a href="{{ route('legal.privacy') }}" class="hover:text-ink transition">Privacy</a>
        </div>
    </footer>
</body>
</html>
