<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-gray-100 min-h-screen">
        <nav class="bg-white border-b px-6 py-3 flex items-center justify-between">
            <div class="flex gap-6 items-center">
                <span class="font-semibold">FitHub Admin</span>
                <a href="/dashboard/plans" class="text-sm text-gray-600 hover:text-gray-900">Plans</a>
                <a href="/dashboard/members" class="text-sm text-gray-600 hover:text-gray-900">Members</a>
                <a href="/dashboard/attendance" class="text-sm text-gray-600 hover:text-gray-900">Attendance</a>
                <a href="/dashboard/classes" class="text-sm text-gray-600 hover:text-gray-900">Classes</a>
                <a href="/dashboard/bookings" class="text-sm text-gray-600 hover:text-gray-900">Bookings</a>
                <a href="/dashboard/progress" class="text-sm text-gray-600 hover:text-gray-900">Progress</a>
            </div>
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">Log out</button>
            </form>
        </nav>

        <main class="p-6">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
