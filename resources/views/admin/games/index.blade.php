<x-layouts.admin title="Games">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Games</h1>
        <a href="{{ route('admin.games.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">+ Nieuw game</a>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Naam</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Teams</th>
                    <th class="px-4 py-3">Kamers</th>
                    <th class="px-4 py-3">Start</th>
                    <th class="px-4 py-3">Einde</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($games as $game)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $game->name }}</td>
                        <td class="px-4 py-3">{{ $game->status->label() }}</td>
                        <td class="px-4 py-3">{{ $game->teams_count }}</td>
                        <td class="px-4 py-3">{{ $game->rooms_count }}</td>
                        <td class="px-4 py-3">{{ $game->start_time?->format('d-m-Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $game->end_time?->format('d-m-Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.games.dashboard', $game) }}" class="text-sm font-semibold text-amber-700 underline">Dashboard</a>
                            <a href="{{ route('admin.games.edit', $game) }}" class="ml-3 text-sm font-semibold text-slate-700 underline">Beheer</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">Nog geen games aangemaakt.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
