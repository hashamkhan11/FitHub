<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>FitHub Admin — Login</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <form method="POST" action="/login" class="bg-white p-8 rounded shadow w-full max-w-sm">
        @csrf

        <h1 class="text-xl font-semibold mb-6">FitHub Admin Login</h1>

        @if ($errors->any())
            <div class="mb-4 text-sm text-red-600">
                {{ $errors->first() }}
            </div>
        @endif

        <label class="block text-sm mb-1" for="email">Email</label>
        <input class="w-full border rounded px-3 py-2 mb-4" type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>

        <label class="block text-sm mb-1" for="password">Password</label>
        <input class="w-full border rounded px-3 py-2 mb-6" type="password" name="password" id="password" required>

        <button class="w-full bg-blue-600 text-white rounded py-2" type="submit">Log in</button>
    </form>
</body>
</html>
