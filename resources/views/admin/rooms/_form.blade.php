@php $room = $room ?? null; @endphp

<div>
    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Naam</label>
    <input id="name" name="name" type="text" value="{{ old('name', $room?->name) }}" required
           class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Korte omschrijving</label>
    <textarea id="description" name="description" rows="2"
              class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">{{ old('description', $room?->description) }}</textarea>
    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="instructions" class="mb-1 block text-sm font-medium text-slate-700">Opdracht / instructies</label>
    <textarea id="instructions" name="instructions" rows="5"
              class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">{{ old('instructions', $room?->instructions) }}</textarea>
    @error('instructions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

@if ($room && !empty($room->images))
    <div>
        <p class="mb-1 block text-sm font-medium text-slate-700">Huidige afbeeldingen</p>
        <div class="grid grid-cols-3 gap-2">
            @foreach ($room->images as $image)
                <div class="relative">
                    <img src="{{ asset('storage/'.$image) }}" class="aspect-square rounded-lg object-cover">
                    <label class="mt-1 flex items-center gap-1 text-xs text-red-700">
                        <input type="checkbox" name="remove_images[]" value="{{ $image }}"> verwijderen
                    </label>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div>
    <label for="images" class="mb-1 block text-sm font-medium text-slate-700">Afbeeldingen toevoegen</label>
    <input id="images" name="images[]" type="file" accept="image/*" multiple
           class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
    @error('images.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="answer" class="mb-1 block text-sm font-medium text-slate-700">Correct antwoord</label>
    <input id="answer" name="answer" type="text" value="{{ old('answer', $room?->answer) }}" required
           class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">
    @error('answer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="alternative_answers" class="mb-1 block text-sm font-medium text-slate-700">Alternatieve antwoorden (één per regel)</label>
    <textarea id="alternative_answers" name="alternative_answers" rows="3"
              class="block w-full rounded-lg border-slate-300 focus:border-slate-900 focus:ring-slate-900">{{ old('alternative_answers', $room ? implode("\n", $room->alternative_answers ?? []) : '') }}</textarea>
    @error('alternative_answers') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    <p class="mt-1 text-xs text-slate-400">Antwoorden worden niet hoofdlettergevoelig vergeleken en spaties aan begin/eind tellen niet mee.</p>
</div>

<label class="flex items-center gap-2 text-sm text-slate-700">
    <input type="hidden" name="active" value="0">
    <input type="checkbox" name="active" value="1" @checked(old('active', $room?->active ?? true)) class="rounded border-slate-300">
    Actief (kamer kan toegewezen worden)
</label>
