<div class="flex flex-1 flex-col" @if($state !== 'finished') wire:poll.5000ms @endif>
    <header class="flex items-center justify-between border-b border-slate-800 px-5 py-4">
        <span class="font-semibold text-slate-300">{{ $team->name }}</span>
        @if ($game->isRunning())
            <div x-data="{
                    remaining: {{ $remainingEventSeconds }},
                }"
                x-init="setInterval(() => { if (remaining > 0) remaining--; }, 1000)"
                class="rounded-full bg-slate-800 px-3 py-1 text-sm font-mono text-amber-400">
                Nog <span x-text="formatDuration(remaining)"></span>
            </div>
        @endif
        <form method="POST" action="{{ route('team.logout') }}">
            @csrf
            <button type="submit" class="text-xs text-slate-500 underline">uitloggen</button>
        </form>
    </header>

    <main class="flex flex-1 flex-col p-6">
        @if ($state === 'not_started')
            <div class="flex flex-1 flex-col items-center justify-center text-center">
                <div class="text-5xl">⏳</div>
                <h1 class="mt-4 text-2xl font-bold">Nog even geduld</h1>
                <p class="mt-2 text-slate-400">Het spel is nog niet gestart. Deze pagina ververst automatisch zodra het begint.</p>
            </div>

        @elseif ($state === 'feedback')
            @php $type = $feedback['type']; @endphp
            <div class="flex flex-1 flex-col items-center justify-center text-center">
                @if ($type === 'correct')
                    <div class="text-6xl">✅</div>
                    <h1 class="mt-4 text-3xl font-extrabold text-emerald-400">GOED!</h1>
                    <p class="mt-1 text-lg text-slate-200">Kamer opgelost.</p>
                    <p class="mt-3 text-2xl font-bold text-amber-400">+{{ $feedback['points'] }} punten</p>
                @elseif ($type === 'failed')
                    <div class="text-6xl">❌</div>
                    <h1 class="mt-4 text-3xl font-extrabold text-red-400">Helaas!</h1>
                    <p class="mt-1 text-lg text-slate-200">Geen pogingen meer over. Kamer verloren.</p>
                    <p class="mt-3 text-lg font-semibold text-slate-400">+0 punten</p>
                @elseif ($type === 'given_up')
                    <div class="text-6xl">🏳️</div>
                    <h1 class="mt-4 text-3xl font-extrabold text-slate-200">Kamer opgegeven</h1>
                    <p class="mt-3 text-lg font-semibold text-slate-400">+0 punten</p>
                @else
                    <div class="text-6xl">⚠️</div>
                    <h1 class="mt-4 text-2xl font-bold text-slate-200">{{ $feedback['message'] ?? 'Er ging iets mis.' }}</h1>
                @endif

                @if (in_array($type, ['correct', 'failed', 'given_up']))
                    <div class="mt-8 w-full">
                        @if ($feedback['nextRoomName'])
                            <p class="text-sm text-slate-400">Ga nu naar:</p>
                            <p class="mt-1 text-2xl font-extrabold tracking-wide">{{ mb_strtoupper($feedback['nextRoomName']) }}</p>
                        @elseif ($feedback['allRoomsPlayed'])
                            <p class="text-slate-300">Jullie hebben alle kamers gespeeld!</p>
                        @elseif ($feedback['waiting'])
                            <p class="text-slate-300">Er is nog geen nieuwe kamer beschikbaar. Blijf op deze pagina.</p>
                        @endif
                    </div>
                @endif

                <button wire:click="continueAfterFeedback"
                        class="mt-10 w-full rounded-xl bg-amber-500 px-4 py-4 text-lg font-bold text-slate-950 active:scale-[0.98]">
                    Verder
                </button>
            </div>

        @elseif ($state === 'finished')
            <div class="flex flex-1 flex-col items-center justify-center text-center">
                <div class="text-5xl">🏁</div>
                <h1 class="mt-4 text-2xl font-extrabold">Het evenement is afgelopen!</h1>
                <div class="mt-8 w-full space-y-4 rounded-2xl bg-slate-900 p-6">
                    <div>
                        <p class="text-sm text-slate-400">Jullie score</p>
                        <p class="text-3xl font-extrabold text-amber-400">{{ $ownResult['points'] }} punten</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400">Actieve speeltijd</p>
                        <p class="text-xl font-bold">{{ gmdate('H:i:s', $ownResult['active_seconds']) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-400">Kamers gespeeld</p>
                        <p class="text-xl font-bold">{{ $ownResult['rooms_played'] }}</p>
                    </div>
                    <div class="border-t border-slate-800 pt-4">
                        <p class="text-sm text-slate-400">Eindklassement</p>
                        <p class="text-xl font-bold">Plaats {{ $ownResult['rank'] }} van de {{ $ownResult['total_teams'] }}</p>
                    </div>
                </div>
            </div>

        @elseif ($state === 'all_played')
            <div class="flex flex-1 flex-col items-center justify-center text-center">
                <div class="text-5xl">🎉</div>
                <h1 class="mt-4 text-2xl font-extrabold">Jullie hebben alle kamers gespeeld!</h1>
                <p class="mt-2 text-slate-400">Wacht op het einde van het evenement voor de uitslag.</p>
            </div>

        @elseif ($state === 'waiting')
            <div class="flex flex-1 flex-col items-center justify-center text-center">
                <div class="text-5xl">🚪</div>
                <h1 class="mt-4 text-2xl font-extrabold">Geen kamer beschikbaar</h1>
                <p class="mt-2 text-slate-400">Er is momenteel geen beschikbare kamer. Blijf op deze pagina, jullie krijgen automatisch een nieuwe kamer zodra er een vrijkomt.</p>
                <div class="mt-6 h-2 w-2 animate-ping rounded-full bg-amber-500"></div>
            </div>

        @elseif ($state === 'assigned')
            <div class="flex flex-1 flex-col items-center justify-center text-center">
                <p class="text-sm uppercase tracking-widest text-slate-400">Jullie volgende kamer</p>
                <h1 class="mt-3 text-4xl font-extrabold tracking-wide">{{ mb_strtoupper($session->room->name) }}</h1>
                <p class="mt-3 text-slate-400">Ga naar {{ $session->room->name }}.</p>

                <button wire:click="startRoom" wire:loading.attr="disabled"
                        class="mt-10 w-full rounded-xl bg-amber-500 px-4 py-5 text-xl font-bold text-slate-950 active:scale-[0.98] disabled:opacity-50">
                    KAMER STARTEN
                </button>
            </div>

        @elseif ($state === 'active')
            <div class="flex flex-1 flex-col">
                <p class="text-center text-sm uppercase tracking-widest text-slate-400">{{ mb_strtoupper($session->room->name) }}</p>
                <h1 class="mt-1 text-center text-lg font-bold text-slate-200">OPDRACHT</h1>

                <div class="mt-4 space-y-4 rounded-2xl bg-slate-900 p-4">
                    @if ($session->room->description)
                        <p class="text-slate-200">{{ $session->room->description }}</p>
                    @endif
                    @if ($session->room->instructions)
                        <p class="whitespace-pre-line text-slate-300">{{ $session->room->instructions }}</p>
                    @endif
                    @if (!empty($session->room->images))
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($session->room->images as $image)
                                <img src="{{ asset('storage/'.$image) }}" alt="" class="rounded-lg object-cover">
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-4 flex items-center justify-between text-sm text-slate-400">
                    <span>Pogingen: {{ $attemptsUsed }} / 3</span>
                    <span x-data="{ elapsed: {{ $activeDurationSeconds }} }" x-init="setInterval(() => elapsed++, 1000)">
                        Tijd: <span class="font-mono" x-text="formatDuration(elapsed)"></span>
                    </span>
                </div>

                @if ($feedback && $feedback['type'] === 'incorrect')
                    <div class="mt-4 rounded-xl bg-red-950 px-4 py-3 text-sm text-red-300">
                        Helaas, dit antwoord is niet goed.
                        Nog {{ $feedback['attemptsRemaining'] }} {{ $feedback['attemptsRemaining'] === 1 ? 'poging' : 'pogingen' }}.
                    </div>
                @endif

                <form wire:submit="submitAnswer" class="mt-4">
                    <label for="answer" class="mb-2 block text-sm font-semibold text-slate-300">Antwoord:</label>
                    <input id="answer" type="text" wire:model="answer" autocomplete="off"
                           class="block w-full rounded-xl border-0 bg-slate-800 px-4 py-4 text-lg text-white focus:ring-2 focus:ring-amber-500">
                    @error('answer') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror

                    <button type="submit" wire:loading.attr="disabled"
                            class="mt-4 w-full rounded-xl bg-amber-500 px-4 py-4 text-lg font-bold text-slate-950 active:scale-[0.98] disabled:opacity-50">
                        ANTWOORD CONTROLEREN
                    </button>
                </form>

                <button wire:click="confirmGiveUp" class="mt-6 text-sm font-semibold text-red-400 underline">
                    KAMER OPGEVEN
                </button>

                @if ($confirmingGiveUp)
                    <div class="fixed inset-0 z-10 flex items-center justify-center bg-black/70 p-6">
                        <div class="w-full max-w-sm rounded-2xl bg-slate-900 p-6 text-center">
                            <p class="text-lg font-semibold text-slate-100">Weet je zeker dat je deze kamer wilt opgeven?</p>
                            <p class="mt-2 text-sm text-slate-400">Je krijgt 0 punten voor deze kamer en kunt deze kamer niet opnieuw spelen.</p>
                            <div class="mt-6 space-y-2">
                                <button wire:click="cancelGiveUp" autofocus class="w-full rounded-xl bg-slate-700 px-4 py-3 font-semibold text-slate-200">ANNULEREN</button>
                                <button wire:click="giveUp" class="w-full rounded-xl bg-red-600 px-4 py-3 font-bold text-white">KAMER OPGEVEN</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </main>
</div>
