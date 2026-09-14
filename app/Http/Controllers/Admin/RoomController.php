<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoomRequest;
use App\Http\Requests\Admin\UpdateRoomRequest;
use App\Models\Game;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Game $game): View
    {
        $rooms = $game->rooms()->withCount('roomSessions')->get()->sortByNatural('name');

        return view('admin.rooms.index', ['game' => $game, 'rooms' => $rooms]);
    }

    public function create(Game $game): View
    {
        return view('admin.rooms.create', ['game' => $game]);
    }

    public function store(StoreRoomRequest $request, Game $game): RedirectResponse
    {
        $room = new Room($request->safe()->only(['name', 'description', 'instructions', 'answer']));
        $room->game_id = $game->id;
        $room->active = $request->boolean('active', true);
        $room->alternative_answers = $this->parseAlternativeAnswers($request->input('alternative_answers'));
        $room->images = $this->storeImages($request);
        $room->save();

        return redirect()->route('admin.games.rooms.index', $game)->with('status', "Kamer \"{$room->name}\" aangemaakt.");
    }

    public function edit(Game $game, Room $room): View
    {
        return view('admin.rooms.edit', ['game' => $game, 'room' => $room]);
    }

    public function update(UpdateRoomRequest $request, Game $game, Room $room): RedirectResponse
    {
        $room->fill($request->safe()->only(['name', 'description', 'instructions', 'answer']));
        $room->active = $request->boolean('active', true);
        $room->alternative_answers = $this->parseAlternativeAnswers($request->input('alternative_answers'));

        $images = collect($room->images ?? [])
            ->reject(fn (string $path) => in_array($path, $request->input('remove_images', []), true))
            ->values()
            ->all();

        foreach (array_diff($room->images ?? [], $images) as $removedPath) {
            Storage::disk('public')->delete($removedPath);
        }

        $room->images = array_merge($images, $this->storeImages($request));
        $room->save();

        return redirect()->route('admin.games.rooms.index', $game)->with('status', 'Kamer bijgewerkt.');
    }

    public function destroy(Game $game, Room $room): RedirectResponse
    {
        foreach ($room->images ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $room->delete();

        return back()->with('status', 'Kamer verwijderd.');
    }

    public function toggleActive(Game $game, Room $room): RedirectResponse
    {
        $room->update(['active' => ! $room->active]);

        return back()->with('status', $room->active ? 'Kamer geactiveerd.' : 'Kamer gedeactiveerd.');
    }

    private function parseAlternativeAnswers(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->values()
            ->all();
    }

    private function storeImages(StoreRoomRequest|UpdateRoomRequest $request): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        return collect($request->file('images'))
            ->map(fn ($file) => $file->store('room-images', 'public'))
            ->all();
    }
}
