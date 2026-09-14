<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin login - Escape Room</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100">
    <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm">
        <h1 class="mb-1 text-xl font-bold text-slate-900">Admin login</h1>
        <p class="mb-6 text-sm text-slate-500">Escape room beheeromgeving</p>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">E-mailadres</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Wachtwoord</label>
                <input id="password" type="password" name="password" required
                       class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300">
                Onthoud mij
            </label>

            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                Inloggen
            </button>
        </form>
    </div>
</body>
</html>
