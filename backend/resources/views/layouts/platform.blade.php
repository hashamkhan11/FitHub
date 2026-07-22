<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'RankSol Platform' }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-graphite min-h-screen font-sans text-ink lg:flex overflow-x-hidden">
        @php
            $items = [
                'overview' => ['Overview', '/ranksol'],
                'gyms' => ['Gyms', '/ranksol/gyms'],
                'plans' => ['Plans', '/ranksol/plans'],
                'activity' => ['Activity', '/ranksol/activity'],
            ];

            $icons = [
                'overview' => '<path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6" rx="0.5"/><rect x="13" y="8" width="3" height="10" rx="0.5"/><rect x="18.5" y="5" width="3" height="13" rx="0.5"/>',
                'gyms' => '<path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/>',
                'plans' => '<path d="M12.586 3H7a2 2 0 0 0-2 2v5.586a1 1 0 0 0 .293.707l8.414 8.414a2 2 0 0 0 2.828 0l5.586-5.586a2 2 0 0 0 0-2.828l-8.414-8.414A1 1 0 0 0 12.586 3Z"/><circle cx="9" cy="9" r="1.3" fill="currentColor" stroke="none"/>',
                'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
            ];

            $currentKey = collect($items)->keys()->first(function ($key) use ($items) {
                $path = $items[$key][1];
                return $path === '/ranksol' ? request()->is('ranksol') : request()->is(ltrim($path, '/').'*');
            });
        @endphp

        {{-- Desktop sidebar --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-graphite-2 border-r border-ink/10 z-20">
            <div class="flex items-center gap-3 px-6 h-16 border-b border-ink/10 shrink-0">
                <div class="w-8 h-8 rounded bg-teal flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                        <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                    </svg>
                </div>
                <span class="font-display font-semibold text-ink tracking-wide text-sm">RANKSOL</span>
            </div>

            <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-0.5">
                @foreach ($items as $key => [$label, $href])
                    @php $active = $key === $currentKey; @endphp
                    <a href="{{ $href }}"
                       class="flex items-center gap-3 px-3 py-2 rounded font-display uppercase text-sm tracking-wide border-l-2 transition
                              {{ $active ? 'text-ink border-teal bg-teal/5' : 'text-mist border-transparent hover:text-ink hover:bg-ink/5' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px] shrink-0">
                            {!! $icons[$key] !!}
                        </svg>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="p-4 border-t border-ink/10 shrink-0">
                <div class="flex items-center gap-3 px-2 mb-3">
                    <div class="w-8 h-8 rounded-full bg-teal/15 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-teal">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-ink text-sm font-medium truncate">{{ auth('platform')->user()->name }}</p>
                        <p class="text-mist text-xs font-mono uppercase truncate">{{ auth('platform')->user()->role }}</p>
                    </div>
                </div>
                <form method="POST" action="/ranksol/logout">
                    @csrf
                    <button type="submit" class="w-full text-left font-display uppercase text-xs tracking-wide text-mist hover:text-tape transition px-2">Log out</button>
                </form>
            </div>
        </aside>

        {{-- Mobile top bar --}}
        <div class="lg:hidden sticky top-0 z-20 bg-graphite-2 border-b border-ink/10">
            <div class="px-4 h-14 flex items-center justify-between">
                <span class="font-display font-semibold text-ink tracking-wide text-sm">RANKSOL</span>
                <form method="POST" action="/ranksol/logout">
                    @csrf
                    <button type="submit" class="font-display uppercase text-xs tracking-wide text-mist hover:text-tape transition">Log out</button>
                </form>
            </div>
            <nav class="flex overflow-x-auto px-2 pb-2 gap-1 no-scrollbar">
                @foreach ($items as $key => [$label, $href])
                    <a href="{{ $href }}"
                       class="shrink-0 px-3 py-1.5 rounded font-display uppercase text-xs tracking-wide transition
                              {{ $key === $currentKey ? 'text-white bg-teal' : 'text-mist hover:text-ink' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Main content --}}
        <div class="flex-1 lg:pl-64 min-w-0">
            <main class="p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
