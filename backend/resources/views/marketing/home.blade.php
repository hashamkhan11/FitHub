<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">

    <title>FitHub — Gym Management Software for Independent Gyms</title>
    <meta name="description" content="FitHub replaces spreadsheets and sign-in sheets with one dashboard for memberships, QR check-in, classes, billing, staff, and revenue insight. 14-day free trial, no card required.">
    <meta name="keywords" content="gym management software, gym membership software, gym check-in app, class booking software for gyms, gym billing software, fitness studio management">
    <meta name="author" content="RankSol">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="FitHub">
    <meta property="og:title" content="FitHub — Gym Management Software for Independent Gyms">
    <meta property="og:description" content="Memberships, QR check-in, classes, billing, staff, and revenue insight — in one dashboard. 14-day free trial, no card required.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('images/branding/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FitHub — Gym Management Software for Independent Gyms">
    <meta name="twitter:description" content="Memberships, QR check-in, classes, billing, staff, and revenue insight — in one dashboard. 14-day free trial, no card required.">
    <meta name="twitter:image" content="{{ asset('images/branding/og-image.png') }}">

    {{-- Icons / PWA manifest --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/branding/favicon-16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/branding/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#0B0F1A">

    {{-- Structured data: Organization --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'RankSol',
        'url' => url('/'),
        'logo' => asset('images/branding/logo.png'),
        'email' => config('app.support_email'),
    ]) !!}
    </script>

    {{-- Structured data: SoftwareApplication + pricing offers --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'FitHub',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web, Android, iOS',
        'description' => 'Gym management software covering memberships, QR check-in, class scheduling, billing, staff access, and revenue analytics for independent gyms and fitness studios.',
        'offers' => $plans->map(fn ($plan) => [
            '@type' => 'Offer',
            'name' => $plan->name,
            'price' => rtrim(rtrim($plan->monthly_price, '0'), '.'),
            'priceCurrency' => 'USD',
            'category' => 'subscription',
        ])->values(),
    ]) !!}
    </script>

    {{-- Structured data: FAQPage (must match the FAQ section below) --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faqs)->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
        ])->values(),
    ]) !!}
    </script>

    @vite('resources/css/app.css')
</head>
<body class="font-sans bg-void text-ink">

    {{-- Nav --}}
    <div class="sticky top-0 z-30 bg-void/80 backdrop-blur-xl border-b border-chalk-3">
        <header class="max-w-6xl mx-auto px-6 lg:px-8 py-5 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                @include('partials.logo', ['class' => 'w-8 h-8', 'textClass' => 'text-xs'])
                <span class="font-display font-semibold tracking-wide text-base">FITHUB</span>
            </a>
            <nav class="hidden md:flex items-center gap-6 text-sm">
                <a href="#features" class="text-steel-2 hover:text-ink transition">Features</a>
                <a href="#pricing" class="text-steel-2 hover:text-ink transition">Pricing</a>
                <a href="#faq" class="text-steel-2 hover:text-ink transition">FAQ</a>
                <a href="#contact" class="text-steel-2 hover:text-ink transition">Contact</a>
            </nav>
            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('login') }}" class="text-steel-2 hover:text-ink transition">Log in</a>
                <a href="/start-trial" class="fh-btn-primary !py-2 !px-4">Start free trial</a>
            </nav>
        </header>
    </div>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="relative max-w-6xl mx-auto px-6 lg:px-8 pt-8 pb-20 lg:pt-12 lg:pb-28">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div class="motion-safe:animate-fade-up">
                    <p class="fh-eyebrow text-gold mb-4">Gym Management, Simplified</p>
                    <h1 class="font-display font-semibold text-6xl lg:text-7xl leading-[1.02] tracking-tighter text-balance mb-6">
                        Run your gym<br>from <span class="text-gold">one board.</span>
                    </h1>
                    <p class="text-steel-2 text-base lg:text-lg leading-relaxed mb-8 max-w-md">
                        Members, classes, attendance, billing, and revenue — tracked live, in one dashboard. No more spreadsheets, no more sign-in sheets.
                    </p>
                    <div class="flex flex-wrap items-center gap-4 mb-4">
                        <a href="/start-trial" class="fh-btn-primary !py-3 !px-6">Start free 14-day trial</a>
                        <a href="#features" class="fh-btn-secondary !py-3 !px-6">See how it works</a>
                    </div>
                    <p class="text-xs text-steel font-mono">No card required</p>
                </div>

                <div class="relative motion-safe:animate-fade-up" style="animation-delay: 120ms">
                    <div class="absolute -inset-6 rounded-xl bg-gradient-to-br from-gold/15 via-gold-2/10 to-transparent blur-2xl"></div>
                    @php
                        $heroFile = collect(['hero-login.webp', 'hero-login.jpg', 'hero-login.png'])
                            ->first(fn ($f) => file_exists(public_path('images/auth/'.$f)));
                    @endphp
                    <div class="relative rounded-xl overflow-hidden border border-chalk-3 aspect-[3/4] shadow-fh-lift">
                        @if ($heroFile)
                            <img src="{{ asset('images/auth/'.$heroFile) }}" alt="Gym member training on the training floor" class="w-full h-full object-cover object-top">
                        @else
                            <div class="w-full h-full bg-chalk flex items-center justify-center">
                                <div class="w-24 h-24 rounded-full bg-gold/10"></div>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-void/70 via-transparent to-transparent"></div>
                        <div class="absolute left-5 right-5 bottom-5 flex items-center justify-between font-mono text-xs">
                            <span class="text-ink font-semibold">Today's check-ins</span>
                            <span class="fh-pill-good">Live</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Problem / pain points --}}
    <section class="relative border-t border-chalk-3 bg-chalk">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="fh-eyebrow text-gold mb-3">Sound familiar?</p>
            <h2 class="fh-heading text-2xl lg:text-3xl mb-10 max-w-xl">Most gyms are still run on tools that were never built for gyms.</h2>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ([
                    ['title' => 'Renewals slip through', 'body' => 'No one notices a membership lapsed until the member has already stopped showing up.'],
                    ['title' => 'Paper sign-in sheets', 'body' => 'Front desk staff manually track attendance — or don\'t track it at all.'],
                    ['title' => 'Revenue is a mystery', 'body' => 'No live view of MRR, churn, or which plans are actually driving revenue.'],
                    ['title' => 'Scattered tools', 'body' => 'A spreadsheet for members, a different app for classes, a notebook for payments.'],
                ] as $pain)
                    <div class="fh-card">
                        <p class="font-display font-semibold text-base mb-1.5">{{ $pain['title'] }}</p>
                        <p class="text-sm text-steel-2 leading-relaxed">{{ $pain['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Feature grid --}}
    <section id="features" class="relative scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="fh-eyebrow text-gold mb-3">What's on the board</p>
            <h2 class="fh-heading text-2xl lg:text-3xl mb-10 max-w-lg">Everything the front desk touches — in one place.</h2>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ([
                    ['title' => 'Members & Plans', 'body' => 'Onboard members, assign plans, and get alerted before a renewal lapses.'],
                    ['title' => 'QR Check-in', 'body' => 'Members scan a QR code at the door — attendance is logged automatically, no sign-in sheets.'],
                    ['title' => 'Classes & Bookings', 'body' => 'Publish a class schedule and let members book — and cancel — their own spot.'],
                    ['title' => 'Billing & Payments', 'body' => 'Stripe-powered subscription billing, with invoices and payment history members can see themselves.'],
                    ['title' => 'Staff & Trainer Access', 'body' => 'Scoped logins for staff and trainers — no full-dashboard handover required.'],
                    ['title' => 'Revenue Insight', 'body' => 'MRR, churn risk, and attendance trends, live — not a month-end spreadsheet exercise.'],
                ] as $feature)
                    <div class="fh-card">
                        <p class="font-display font-semibold text-base mb-1.5">{{ $feature['title'] }}</p>
                        <p class="text-sm text-steel-2 leading-relaxed">{{ $feature['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="relative border-t border-chalk-3 bg-chalk">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="fh-eyebrow text-gold mb-3">How it works</p>
            <h2 class="fh-heading text-2xl lg:text-3xl mb-10 max-w-lg">Up and running in an afternoon, not a quarter.</h2>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ([
                    ['step' => '01', 'title' => 'Add your members', 'body' => 'Import your existing roster or add members one by one — plans and pricing come with them.'],
                    ['step' => '02', 'title' => 'Set up plans & classes', 'body' => 'Define membership tiers and publish your class schedule.'],
                    ['step' => '03', 'title' => 'Members check in & book', 'body' => 'Members scan in at the door and book classes from their own app.'],
                    ['step' => '04', 'title' => 'Track it all live', 'body' => 'Watch attendance, renewals, and revenue update on your dashboard in real time.'],
                ] as $item)
                    <div>
                        <p class="fh-td-mono text-gold text-sm mb-2">{{ $item['step'] }}</p>
                        <p class="font-display font-semibold text-base mb-1.5">{{ $item['title'] }}</p>
                        <p class="text-sm text-steel-2 leading-relaxed">{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Dual interface --}}
    <section class="relative">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="fh-eyebrow text-gold mb-3">One system, two sides</p>
            <h2 class="fh-heading text-2xl lg:text-3xl mb-10 max-w-lg">A dashboard for your staff. An app for your members.</h2>

            <div class="grid lg:grid-cols-2 gap-6">
                <div class="fh-card">
                    <p class="fh-eyebrow text-gold mb-2">For your team</p>
                    <p class="font-display font-semibold text-lg mb-3">The operator dashboard.</p>
                    <ul class="text-sm text-steel-2 space-y-2">
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> Manage members, plans, staff, and classes from one screen</li>
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> Live attendance and revenue dashboards</li>
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> Scoped staff logins, so you control who sees what</li>
                    </ul>
                </div>
                <div class="fh-card">
                    <p class="fh-eyebrow text-gold mb-2">For your members</p>
                    <p class="font-display font-semibold text-lg mb-3">The member app.</p>
                    <ul class="text-sm text-steel-2 space-y-2">
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> QR check-in at the door — no cards, no front-desk queue</li>
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> Book and cancel classes from their phone</li>
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> View plan status, invoices, and attendance streaks</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Why FitHub: trust section, no fake testimonials --}}
    <section class="relative border-t border-chalk-3 bg-chalk">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="fh-eyebrow text-gold mb-3">Why FitHub</p>
            <h2 class="fh-heading text-2xl lg:text-3xl mb-10 max-w-lg">Built for independent gyms, not enterprise chains.</h2>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ([
                    ['title' => 'No contracts', 'body' => 'Month-to-month billing. Cancel anytime, no long-term lock-in.'],
                    ['title' => '14-day free trial', 'body' => 'Try the full dashboard with no card required.'],
                    ['title' => 'Your data, your gym', 'body' => 'Every gym\'s data is isolated — nothing shared across accounts.'],
                    ['title' => 'Real support', 'body' => 'Reach an actual person when something needs fixing — see contact below.'],
                ] as $reason)
                    <div class="fh-card">
                        <p class="font-display font-semibold text-base mb-1.5">{{ $reason['title'] }}</p>
                        <p class="text-sm text-steel-2 leading-relaxed">{{ $reason['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="relative scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="fh-eyebrow text-gold mb-3">Pricing</p>
            <h2 class="fh-heading text-2xl lg:text-3xl mb-3">One plan for every stage of your gym.</h2>
            <p class="text-steel-2 text-sm mb-10">14-day free trial. No card required.</p>

            <div class="grid sm:grid-cols-3 gap-5">
                @forelse ($plans as $plan)
                    <div class="fh-card flex flex-col {{ $loop->iteration === 2 ? 'border-gold/40 shadow-fh-glow -translate-y-1' : '' }}">
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

    {{-- FAQ --}}
    <section id="faq" class="relative border-t border-chalk-3 bg-chalk scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="fh-eyebrow text-gold mb-3">Questions</p>
            <h2 class="fh-heading text-2xl lg:text-3xl mb-10 max-w-lg">Frequently asked questions.</h2>

            <div class="max-w-3xl space-y-3">
                @foreach ($faqs as $faq)
                    <details class="fh-card group">
                        <summary class="cursor-pointer list-none flex items-center justify-between gap-4 font-display font-semibold text-base">
                            {{ $faq['q'] }}
                            <span class="text-gold text-lg group-open:rotate-45 transition-transform">+</span>
                        </summary>
                        <p class="text-sm text-steel-2 leading-relaxed mt-3">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="relative border-t border-chalk-3 overflow-hidden">
        <div class="absolute left-1/2 top-0 -translate-x-1/2 w-[420px] h-[420px] rounded-full bg-gold/10 blur-[100px] pointer-events-none"></div>
        <div class="relative max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20 text-center">
            <h2 class="fh-heading text-2xl lg:text-3xl mb-4">Ready to get off the spreadsheet?</h2>
            <p class="text-steel-2 text-sm mb-8">No card required for the first 14 days.</p>
            <a href="/start-trial" class="fh-btn-primary !py-3 !px-8">Start free trial</a>
        </div>
    </section>

    {{-- Contact --}}
    <section id="contact" class="relative border-t border-chalk-3 bg-chalk scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <div class="grid lg:grid-cols-2 gap-12">
                <div>
                    <p class="fh-eyebrow text-gold mb-3">Contact</p>
                    <h2 class="fh-heading text-2xl lg:text-3xl mb-4">Questions before you start? Talk to us.</h2>
                    <p class="text-steel-2 text-sm leading-relaxed mb-6">Send a message and we'll reply within one business day, or email us directly.</p>
                    <a href="mailto:{{ config('app.support_email') }}" class="fh-btn-secondary !py-2.5 !px-5 inline-block">{{ config('app.support_email') }}</a>
                </div>

                <div class="fh-card">
                    @if (session('status'))
                        <p class="fh-pill-good mb-4">{{ session('status') }}</p>
                    @endif
                    <form method="POST" action="{{ route('contact') }}" class="space-y-4">
                        @csrf
                        <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                        <div>
                            <label for="contact-name" class="text-xs text-steel-2 block mb-1.5">Name</label>
                            <input id="contact-name" type="text" name="name" required value="{{ old('name') }}" class="fh-input w-full">
                            @error('name') <p class="text-tape text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="contact-email" class="text-xs text-steel-2 block mb-1.5">Email</label>
                            <input id="contact-email" type="email" name="email" required value="{{ old('email') }}" class="fh-input w-full">
                            @error('email') <p class="text-tape text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="contact-gym" class="text-xs text-steel-2 block mb-1.5">Gym name (optional)</label>
                            <input id="contact-gym" type="text" name="gym_name" value="{{ old('gym_name') }}" class="fh-input w-full">
                        </div>

                        <div>
                            <label for="contact-message" class="text-xs text-steel-2 block mb-1.5">Message</label>
                            <textarea id="contact-message" name="message" required rows="4" class="fh-input w-full">{{ old('message') }}</textarea>
                            @error('message') <p class="text-tape text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="fh-btn-primary w-full !py-2.5">Send message</button>
                    </form>
                </div>
            </div>
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
