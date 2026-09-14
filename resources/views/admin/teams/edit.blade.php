<x-layouts.admin :game="$game" title="Team bewerken">
    <h1 class="mb-6 text-2xl font-bold text-slate-900">Team bewerken</h1>

    <form method="POST" action="{{ route('admin.games.teams.update', [$game, $team]) }}" class="max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Teamnaam</label>
            <input id="name" name="name" type="text" value="{{ old('name', $team->name) }}" required
                   class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" @checked(old('active', $team->active)) class="rounded border-slate-300">
            Actief (team mag inloggen en spelen)
        </label>

        <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
            Opslaan
        </button>
    </form>
</x-layouts.admin>
