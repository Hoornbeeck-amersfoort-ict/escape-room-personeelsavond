<x-layouts.admin :game="$game" title="Teams">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Teams — {{ $game->name }}</h1>
        <a href="{{ route('admin.games.teams.create', $game) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">+ Team toevoegen</a>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Naam</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Sessies</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($teams as $team)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $team->name }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $team->active ? 'text-emerald-600' : 'text-red-600' }} font-semibold">
                                {{ $team->active ? 'Actief' : 'Geblokkeerd' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $team->room_sessions_count }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('admin.games.teams.edit', [$game, $team]) }}" class="font-semibold text-amber-700 underline">Bewerken</a>

                            <form method="POST" action="{{ route('admin.games.teams.reset-code', [$game, $team]) }}" class="inline">
                                @csrf
                                <button type="submit" class="ml-3 font-semibold text-slate-700 underline">Code resetten</button>
                            </form>

                            <form method="POST" action="{{ route('admin.games.teams.toggle-active', [$game, $team]) }}" class="inline">
                                @csrf
                                <button type="submit" class="ml-3 font-semibold text-slate-700 underline">
                                    {{ $team->active ? 'Blokkeren' : 'Activeren' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.games.teams.destroy', [$game, $team]) }}" class="inline" onsubmit="return confirm('Team en alle bijbehorende sessies verwijderen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ml-3 font-semibold text-red-700 underline">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">Nog geen teams.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
