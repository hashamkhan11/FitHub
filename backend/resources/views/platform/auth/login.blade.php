<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RankSol Platform — Sign in</title>

    @vite('resources/css/app.css')
</head>
<body class="font-sans bg-graphite">
    <div class="min-h-screen flex items-center justify-center px-6 py-14">
        <div class="w-full max-w-sm motion-safe:animate-fade-up">
            <div class="flex items-center gap-2 mb-10 justify-center">
                <div class="w-8 h-8 rounded bg-teal flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                        <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                    </svg>
                </div>
                <span class="font-display font-semibold tracking-wide text-ink">RANKSOL PLATFORM</span>
            </div>

            <p class="pf-eyebrow text-center mb-2">Internal Access</p>
            <h1 class="pf-heading text-2xl text-center mb-8">Sign in to continue</h1>

            @if ($errors->any())
                <div class="pf-error mb-5 border border-tape/30 bg-tape/5 rounded px-3 py-2 text-center">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="/ranksol/login" class="space-y-5">
                @csrf

                <div>
                    <label class="pf-label" for="email">Email</label>
                    <input class="pf-input" type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
                </div>

                <div>
                    <label class="pf-label" for="password">Password</label>
                    <input class="pf-input" type="password" name="password" id="password" required>
                </div>

                <button class="pf-btn-primary w-full !py-3" type="submit">Log in</button>
            </form>

            <p class="text-xs text-mist-2 mt-10 text-center">RankSol staff only · not for gym owners or members</p>
        </div>
    </div>
</body>
</html>
