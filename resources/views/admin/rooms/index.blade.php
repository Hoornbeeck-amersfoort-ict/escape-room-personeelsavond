<x-layouts.admin :game="$game" title="Kamers">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Kamers — {{ $game->name }}</h1>
        <a href="{{ route('admin.games.rooms.create', $game) }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">+ Kamer toevoegen</a>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Naam</th>
                    <th class="px-4 py-3">Antwoord</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Sessies</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rooms as $room)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $room->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $room->answer }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $room->active ? 'text-emerald-600' : 'text-red-600' }} font-semibold">
                                {{ $room->active ? 'Actief' : 'Inactief' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $room->room_sessions_count }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('admin.games.rooms.edit', [$game, $room]) }}" class="font-semibold text-amber-700 underline">Bewerken</a>

                            <form method="POST" action="{{ route('admin.games.rooms.toggle-active', [$game, $room]) }}" class="inline">
                                @csrf
                                <button type="submit" class="ml-3 font-semibold text-slate-700 underline">
                                    {{ $room->active ? 'Deactiveren' : 'Activeren' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.games.rooms.destroy', [$game, $room]) }}" class="inline" onsubmit="return confirm('Kamer en alle bijbehorende sessies verwijderen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ml-3 font-semibold text-red-700 underline">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">Nog geen kamers.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
