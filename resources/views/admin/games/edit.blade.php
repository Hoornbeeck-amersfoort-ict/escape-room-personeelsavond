<x-layouts.admin :game="$game" title="{{ $game->name }}">
    <h1 class="mb-1 text-2xl font-bold text-slate-900">{{ $game->name }}</h1>
    <p class="mb-6 text-sm text-slate-500">Status: <span class="font-semibold">{{ $game->status->label() }}</span></p>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-bold text-slate-900">Instellingen</h2>
            <form method="POST" action="{{ route('admin.games.update', $game) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Naam</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $game->name) }}" required
                           class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="start_time" class="mb-1 block text-sm font-medium text-slate-700">Starttijd</label>
                    <input id="start_time" name="start_time" type="datetime-local"
                           value="{{ old('start_time', $game->start_time?->format('Y-m-d\TH:i')) }}"
                           class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
                    @error('start_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="end_time" class="mb-1 block text-sm font-medium text-slate-700">Eindtijd</label>
                    <input id="end_time" name="end_time" type="datetime-local"
                           value="{{ old('end_time', $game->end_time?->format('Y-m-d\TH:i')) }}"
                           class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
                    @error('end_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                    Opslaan
                </button>
            </form>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 font-bold text-slate-900">Overzicht</h2>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('admin.games.teams.index', $game) }}" class="font-semibold text-amber-700 underline">{{ $game->teams()->count() }} teams beheren</a></li>
                    <li><a href="{{ route('admin.games.rooms.index', $game) }}" class="font-semibold text-amber-700 underline">{{ $game->rooms()->count() }} kamers beheren</a></li>
                    <li><a href="{{ route('admin.games.dashboard', $game) }}" class="font-semibold text-amber-700 underline">Live dashboard bekijken</a></li>
                </ul>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 font-bold text-slate-900">Spelbeheer</h2>
                <div class="space-y-2">
                    <form method="POST" action="{{ route('admin.games.start', $game) }}" onsubmit="return confirm('Game starten?');">
                        @csrf
                        <button type="submit" @disabled($game->status->value !== 'draft' || $game->end_time === null)
                                class="w-full rounded-lg bg-emerald-600 px-4 py-3 font-semibold text-white disabled:opacity-40">
                            Game starten
                        </button>
                        @if ($game->status->value === 'draft' && $game->end_time === null)
                            <p class="mt-1 text-xs text-slate-500">Stel eerst een eindtijd in hierboven.</p>
                        @endif
                    </form>
                    <form method="POST" action="{{ route('admin.games.finish', $game) }}" onsubmit="return confirm('Weet je zeker dat je het game nu wilt beëindigen?');">
                        @csrf
                        <button type="submit" @disabled($game->status->value !== 'running')
                                class="w-full rounded-lg bg-red-600 px-4 py-3 font-semibold text-white disabled:opacity-40">
                            Game beëindigen
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.games.reset', $game) }}" onsubmit="return confirm('Alle voortgang (sessies, punten, pogingen) wordt gewist. Doorgaan?');">
                        @csrf
                        <button type="submit" class="w-full rounded-lg bg-slate-200 px-4 py-3 font-semibold text-slate-800">
                            Game resetten
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.games.destroy', $game) }}" onsubmit="return confirm('Dit game inclusief teams en kamers permanent verwijderen?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full rounded-lg px-4 py-3 font-semibold text-red-700 underline">
                            Game verwijderen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
