@props(['game' => null])
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Escape Room Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 flex-col bg-slate-900 text-slate-100 sm:flex">
            <div class="px-6 py-5 text-lg font-bold tracking-tight">🔐 Escape Room</div>
            <nav class="flex-1 space-y-1 px-3">
                <a href="{{ route('admin.games.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 {{ request()->routeIs('admin.games.index') ? 'bg-slate-800' : '' }}">Games</a>
                @if (isset($game))
                    <div class="mt-4 border-t border-slate-800 pt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $game->name }}</div>
                    <a href="{{ route('admin.games.dashboard', $game) }}" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 {{ request()->routeIs('admin.games.dashboard') ? 'bg-slate-800' : '' }}">Live dashboard</a>
                    <a href="{{ route('admin.games.edit', $game) }}" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 {{ request()->routeIs('admin.games.edit') ? 'bg-slate-800' : '' }}">Game instellingen</a>
                    <a href="{{ route('admin.games.teams.index', $game) }}" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 {{ request()->routeIs('admin.games.teams.*') ? 'bg-slate-800' : '' }}">Teams</a>
                    <a href="{{ route('admin.games.rooms.index', $game) }}" class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 {{ request()->routeIs('admin.games.rooms.*') ? 'bg-slate-800' : '' }}">Kamers</a>
                @endif
                <a href="{{ route('admin.audit-logs.index') }}" class="mt-4 block rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 {{ request()->routeIs('admin.audit-logs.*') ? 'bg-slate-800' : '' }}">Audit log</a>
            </nav>
            <form method="POST" action="{{ route('admin.logout') }}" class="p-3">
                @csrf
                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-300 hover:bg-slate-800">Uitloggen ({{ auth()->user()?->name }})</button>
            </form>
        </aside>

        <div class="flex-1">
            <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 sm:hidden">
                <span class="text-lg font-bold">🔐 Escape Room</span>
                <a href="{{ route('admin.games.index') }}" class="text-sm font-medium text-slate-600">Games</a>
            </header>

            <main class="p-4 sm:p-8">
                @if (session('status'))
                    <div class="mb-6 rounded-lg bg-emerald-100 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-6 rounded-lg bg-red-100 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
