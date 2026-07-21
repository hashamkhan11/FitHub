<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitHub Admin — Login</title>

    @if (file_exists(public_path('images/branding/logo.png')))
        <link rel="icon" type="image/png" href="{{ asset('images/branding/logo.png') }}">
    @endif

    @vite('resources/css/app.css')
</head>
<body class="font-sans">
    <div class="min-h-screen lg:grid lg:grid-cols-5">

        {{-- Hero panel --}}
        <div class="relative lg:col-span-3 min-h-[42vh] lg:min-h-screen flex flex-col justify-between overflow-hidden bg-ink">
            @php
                $heroFile = collect(['hero-login.webp', 'hero-login.jpg', 'hero-login.png'])
                    ->first(fn ($f) => file_exists(public_path('images/auth/'.$f)));
            @endphp
            @if ($heroFile)
                <img src="{{ asset('images/auth/'.$heroFile) }}" alt="" class="absolute inset-0 w-full h-full object-cover object-top">
                <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/75 to-ink/25"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-ink/50 via-ink/10 to-transparent"></div>
            @else
                <div class="absolute inset-0 opacity-[0.07]"
                     style="background-image: repeating-linear-gradient(135deg, #D9A441 0px, #D9A441 1px, transparent 1px, transparent 28px);"></div>
                <div class="absolute -top-32 -left-24 w-[28rem] h-[28rem] rounded-full bg-gold/20 blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-[32rem] h-[32rem] rounded-full bg-gold-2/10 blur-3xl"></div>
            @endif

            <div class="relative p-8 lg:p-12 flex items-center gap-3 motion-safe:animate-fade-up">
                @include('partials.logo', ['class' => 'w-9 h-9', 'textClass' => 'text-sm'])
                <span class="font-display font-semibold tracking-wide text-lg text-chalk">FITHUB</span>
            </div>

            <div class="relative p-8 lg:p-12 pb-14 lg:pb-16 max-w-xl motion-safe:animate-fade-up" style="animation-delay: 120ms">
                <p class="fh-eyebrow text-gold-2 mb-3">Gym Operations, On One Board</p>
                <h1 class="font-display font-bold uppercase text-4xl lg:text-5xl leading-[1.05] tracking-tight text-balance text-chalk mb-5">
                    Run the floor.<br class="hidden lg:block"> Not the spreadsheets.
                </h1>
                <p class="text-steel-2 text-sm lg:text-base leading-relaxed mb-8 max-w-md">
                    Members, classes, attendance, and revenue — tracked live, in one
                    scoreboard built for the front desk.
                </p>

                <div class="grid grid-cols-3 gap-3 lg:gap-6 max-w-lg font-mono text-xs">
                    <div class="border-t-2 border-gold/60 pt-2">
                        <p class="text-chalk font-semibold">Live</p>
                        <p class="text-steel-2">Check-ins & QR</p>
                    </div>
                    <div class="border-t-2 border-gold/60 pt-2">
                        <p class="text-chalk font-semibold">Auto</p>
                        <p class="text-steel-2">Renewals & alerts</p>
                    </div>
                    <div class="border-t-2 border-gold/60 pt-2">
                        <p class="text-chalk font-semibold">Full</p>
                        <p class="text-steel-2">Revenue insight</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form panel --}}
        <div class="lg:col-span-2 flex items-center justify-center bg-chalk px-6 py-14 sm:px-12">
            <div class="w-full max-w-sm motion-safe:animate-fade-up" style="animation-delay: 180ms">
                <p class="fh-eyebrow mb-2">Staff Sign-in</p>
                <h2 class="fh-heading text-2xl mb-8">Welcome back</h2>

                @if ($errors->any())
                    <div class="fh-error mb-5 border border-tape/30 bg-tape/5 rounded px-3 py-2">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="/login" class="space-y-5">
                    @csrf

                    <div>
                        <label class="fh-label" for="email">Email</label>
                        <input class="fh-input" type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
                    </div>

                    <div>
                        <label class="fh-label" for="password">Password</label>
                        <input class="fh-input" type="password" name="password" id="password" required>
                    </div>

                    <button class="fh-btn-primary w-full !py-3" type="submit">Log in</button>
                </form>

                <p class="text-xs text-steel mt-10">FitHub Admin · built for RankSol</p>
            </div>
        </div>
    </div>
</body>
</html>
