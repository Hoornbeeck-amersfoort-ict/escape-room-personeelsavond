<x-layouts.team title="Escape Room - Inloggen">
    <div class="flex flex-1 flex-col justify-center px-6 py-12">
        <div class="mb-10 text-center">
            <div class="text-4xl">🔐</div>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white">ESCAPE ROOM</h1>
            <p class="mt-1 text-sm text-slate-400">Log in met je teamnaam en teamcode</p>
        </div>

        <form method="POST" action="{{ route('team.login.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="team_id" class="mb-2 block text-sm font-semibold text-slate-300">Team</label>
                <select id="team_id" name="team_id" required
                        class="block w-full rounded-xl border-0 bg-slate-800 px-4 py-4 text-lg text-white focus:ring-2 focus:ring-amber-500">
                    <option value="" disabled selected>Kies je team...</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected(old('team_id') == $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
                @error('team_id') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="code" class="mb-2 block text-sm font-semibold text-slate-300">Teamcode</label>
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="off" required
                       class="block w-full rounded-xl border-0 bg-slate-800 px-4 py-4 text-center text-2xl tracking-[0.5em] text-white focus:ring-2 focus:ring-amber-500"
                       placeholder="••••" maxlength="8">
                @error('code') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-amber-500 px-4 py-4 text-lg font-bold text-slate-950 shadow-lg shadow-amber-500/20 transition hover:bg-amber-400 active:scale-[0.98]">
                START
            </button>
        </form>

        <p class="mt-8 text-center text-xs text-slate-500">
            Ben je organisator? <a href="{{ route('admin.login') }}" class="underline">Admin login</a>
        </p>
    </div>
</x-layouts.team>
