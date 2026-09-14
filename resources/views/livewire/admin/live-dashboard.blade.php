<div wire:poll.5000ms>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $game->name }}</h1>
            <p class="text-sm text-slate-500">
                Status:
                <span @class([
                    'font-semibold',
                    'text-slate-500' => $game->status->value === 'draft',
                    'text-emerald-600' => $game->status->value === 'running',
                    'text-red-600' => $game->status->value === 'finished',
                ])>{{ $game->status->label() }}</span>
            </p>
        </div>

        @if ($game->isRunning())
            <div class="rounded-xl bg-slate-900 px-4 py-2 font-mono text-lg text-amber-400">
                Nog {{ gmdate('H:i:s', $remainingEventSeconds) }}
            </div>
        @endif
    </div>

    <div class="mb-6 flex gap-2 border-b border-slate-200">
        @foreach (['teams' => 'Teamoverzicht', 'rooms' => 'Kamerbezetting', 'leaderboard' => 'Leaderboard'] as $key => $label)
            <button wire:click="setTab('{{ $key }}')"
                    @class([
                        'border-b-2 px-4 py-2 text-sm font-semibold',
                        'border-slate-900 text-slate-900' => $tab === $key,
                        'border-transparent text-slate-500 hover:text-slate-800' => $tab !== $key,
                    ])>
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'teams')
        <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Team</th>
                        <th class="px-4 py-3">Kamer</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Punten</th>
                        <th class="px-4 py-3">Actieve tijd</th>
                        <th class="px-4 py-3">Kamers gespeeld</th>
                        <th class="px-4 py-3">Acties</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($teamsOverview as $row)
                        <tr class="{{ $row['team']->active ? '' : 'bg-red-50' }}">
                            <td class="px-4 py-3 font-semibold text-slate-900">
                                {{ $row['team']->name }}
                                @unless ($row['team']->active)
                                    <span class="ml-1 rounded bg-red-100 px-1.5 py-0.5 text-xs text-red-700">geblokkeerd</span>
                                @endunless
                            </td>
                            <td class="px-4 py-3">{{ $row['current_room']->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $row['current_session']?->status->label() ?? 'Geen actieve kamer' }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $row['points'] }}</td>
                            <td class="px-4 py-3 font-mono">{{ gmdate('H:i:s', $row['active_seconds']) }}</td>
                            <td class="px-4 py-3">{{ $row['rooms_played'] }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button wire:click="openManualAssign({{ $row['team']->id }})" class="text-xs font-semibold text-amber-700 underline">Kamer toewijzen</button>
                                    @if ($row['current_session'])
                                        <button wire:click="confirmResetSession({{ $row['current_session']->id }})" class="text-xs font-semibold text-red-700 underline">Reset sessie</button>
                                        <button wire:click="openAdjustScore({{ $row['current_session']->id }})" class="text-xs font-semibold text-slate-700 underline">Score aanpassen</button>
                                    @endif
                                    <button wire:click="toggleTeamActive({{ $row['team']->id }})" class="text-xs font-semibold text-slate-700 underline">
                                        {{ $row['team']->active ? 'Blokkeer' : 'Activeer' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif ($tab === 'rooms')
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($roomsOverview as $room)
                @php
                    $count = $room->active_teams_count;
                    $color = match (true) {
                        $count === 0 => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                        $count === 1 => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                        $count === 2 => 'bg-orange-100 text-orange-800 border-orange-300',
                        default => 'bg-red-100 text-red-800 border-red-300',
                    };
                @endphp
                <div class="rounded-xl border-2 p-4 text-center {{ $color }} {{ $room->active ? '' : 'opacity-40' }}">
                    <p class="font-bold">{{ $room->name }}</p>
                    <p class="text-2xl font-extrabold">{{ $count }}</p>
                    <p class="text-xs">{{ $count === 1 ? 'team' : 'teams' }}</p>
                    @unless ($room->active)
                        <p class="mt-1 text-xs font-semibold">inactief</p>
                    @endunless
                </div>
            @endforeach
        </div>
    @elseif ($tab === 'leaderboard')
        <div class="overflow-x-auto rounded-xl bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Team</th>
                        <th class="px-4 py-3">Punten</th>
                        <th class="px-4 py-3">Actieve tijd</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($standings as $row)
                        <tr>
                            <td class="px-4 py-3 font-bold">{{ $row['rank'] }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $row['team']->name }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $row['points'] }}</td>
                            <td class="px-4 py-3 font-mono">{{ gmdate('H:i:s', $row['active_seconds']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($manualAssignTeamId)
        <div class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-6">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6">
                <h2 class="text-lg font-bold">Kies een kamer</h2>
                <div class="mt-4 max-h-64 space-y-2 overflow-y-auto">
                    @foreach ($allRoomsForAssign as $room)
                        <button wire:click="assignRoom({{ $room->id }})" class="block w-full rounded-lg border border-slate-200 px-4 py-2 text-left hover:bg-slate-50">
                            {{ $room->name }}
                        </button>
                    @endforeach
                </div>
                <button wire:click="cancelManualAssign" class="mt-4 w-full rounded-lg bg-slate-100 px-4 py-2 font-semibold text-slate-700">Annuleren</button>
            </div>
        </div>
    @endif

    @if ($resettingSessionId)
        <div class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-6">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center">
                <p class="font-semibold text-slate-900">Weet je zeker dat je deze sessie wilt resetten?</p>
                <p class="mt-2 text-sm text-slate-500">De sessie wordt verwijderd en het team krijgt automatisch een nieuwe kamer.</p>
                <div class="mt-4 flex gap-2">
                    <button wire:click="resetSession" class="flex-1 rounded-lg bg-red-600 px-4 py-2 font-semibold text-white">Resetten</button>
                    <button wire:click="cancelResetSession" class="flex-1 rounded-lg bg-slate-100 px-4 py-2 font-semibold text-slate-700">Annuleren</button>
                </div>
            </div>
        </div>
    @endif

    @if ($adjustingScoreSessionId)
        <div class="fixed inset-0 z-10 flex items-center justify-center bg-black/40 p-6">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6">
                <h2 class="text-lg font-bold">Score aanpassen</h2>
                <input type="number" wire:model="adjustScoreValue" min="0" max="3" class="mt-4 block w-full rounded-lg border-slate-300">
                @error('adjustScoreValue') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="mt-4 flex gap-2">
                    <button wire:click="saveAdjustScore" class="flex-1 rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white">Opslaan</button>
                    <button wire:click="cancelAdjustScore" class="flex-1 rounded-lg bg-slate-100 px-4 py-2 font-semibold text-slate-700">Annuleren</button>
                </div>
            </div>
        </div>
    @endif
</div>
