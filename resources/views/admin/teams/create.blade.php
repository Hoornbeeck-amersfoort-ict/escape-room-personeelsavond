<x-layouts.admin :game="$game" title="Nieuw team">
    <h1 class="mb-6 text-2xl font-bold text-slate-900">Nieuw team — {{ $game->name }}</h1>

    <form method="POST" action="{{ route('admin.games.teams.store', $game) }}" class="max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Teamnaam</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                   class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="code" class="mb-1 block text-sm font-medium text-slate-700">Teamcode (4 cijfers)</label>
            <input id="code" name="code" type="text" inputmode="numeric" maxlength="4" value="{{ old('code') }}" required
                   class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
            @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
            Aanmaken
        </button>
    </form>
</x-layouts.admin>
