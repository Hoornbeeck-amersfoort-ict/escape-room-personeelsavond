<x-layouts.admin :game="$game" title="Kamer bewerken">
    <h1 class="mb-6 text-2xl font-bold text-slate-900">Kamer bewerken — {{ $game->name }}</h1>

    <form method="POST" action="{{ route('admin.games.rooms.update', [$game, $room]) }}" enctype="multipart/form-data"
          class="max-w-2xl space-y-4 rounded-xl bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('admin.rooms._form')

        <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
            Opslaan
        </button>
    </form>
</x-layouts.admin>
