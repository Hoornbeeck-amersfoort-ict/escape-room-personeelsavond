<?php

namespace Database\Seeders;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Room;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The PIN every seeded team uses in development, so it's easy to log in
     * and test with. See the README for the full list of test accounts.
     */
    public const DEV_TEAM_CODE = '1234';

    /**
     * Seed the application's database with one ready-to-play development
     * game: 15 teams, 17 rooms with real trivia questions, and an admin user.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Beheerder',
            'email' => 'admin@example.com',
        ]);

        $game = Game::create([
            'name' => 'Personeelsavond Escape Room',
            'status' => GameStatus::Draft,
            'start_time' => null,
            'end_time' => null,
        ]);

        collect(range(1, 15))->each(function (int $number) use ($game) {
            $team = new Team(['name' => "Team {$number}"]);
            $team->game_id = $game->id;
            $team->setCode(self::DEV_TEAM_CODE);
            $team->save();
        });

        foreach ($this->trivia() as $room) {
            Room::create([
                'game_id' => $game->id,
                'name' => $room['name'],
                'description' => $room['description'],
                'instructions' => $room['instructions'],
                'answer' => $room['answer'],
                'alternative_answers' => $room['alternative_answers'] ?? [],
                'active' => true,
            ]);
        }

        $this->command?->info('Seeded 1 game, 15 teams (PIN '.self::DEV_TEAM_CODE.'), 17 rooms.');
        $this->command?->info('Admin login: admin@example.com / password');
    }

    /**
     * @return array<int, array{name: string, description: string, instructions: string, answer: string, alternative_answers?: array<int, string>}>
     */
    private function trivia(): array
    {
        return [
            ['name' => 'Kamer 1', 'description' => 'Aardrijkskunde', 'instructions' => 'Wat is de hoofdstad van Nederland?', 'answer' => 'Amsterdam'],
            ['name' => 'Kamer 2', 'description' => 'Wiskunde', 'instructions' => 'Wat is de uitkomst van 12 x 12?', 'answer' => '144'],
            ['name' => 'Kamer 3', 'description' => 'Geschiedenis', 'instructions' => 'In welk jaar eindigde de Tweede Wereldoorlog?', 'answer' => '1945'],
            ['name' => 'Kamer 4', 'description' => 'Natuur', 'instructions' => 'Wat is het grootste zoogdier ter wereld?', 'answer' => 'blauwe vinvis', 'alternative_answers' => ['vinvis', 'blauwe walvis']],
            ['name' => 'Kamer 5', 'description' => 'Scheikunde', 'instructions' => 'Wat is het chemisch symbool voor goud?', 'answer' => 'Au'],
            ['name' => 'Kamer 6', 'description' => 'Sport', 'instructions' => 'Hoeveel spelers staan er in een voetbalteam op het veld (exclusief wissels)?', 'answer' => '11', 'alternative_answers' => ['elf']],
            ['name' => 'Kamer 7', 'description' => 'Muziek', 'instructions' => 'Hoeveel snaren heeft een standaard gitaar?', 'answer' => '6', 'alternative_answers' => ['zes']],
            ['name' => 'Kamer 8', 'description' => 'Film', 'instructions' => 'Welk dier is de hoofdrolspeler in de film "Nemo"?', 'answer' => 'clownvis', 'alternative_answers' => ['vis', 'clownsvis']],
            ['name' => 'Kamer 9', 'description' => 'Aardrijkskunde', 'instructions' => 'Wat is de langste rivier ter wereld?', 'answer' => 'Nijl'],
            ['name' => 'Kamer 10', 'description' => 'Wiskunde', 'instructions' => 'Hoeveel graden heeft een rechte hoek?', 'answer' => '90'],
            ['name' => 'Kamer 11', 'description' => 'Techniek', 'instructions' => 'Welk bedrijf ontwikkelde het besturingssysteem Windows?', 'answer' => 'Microsoft'],
            ['name' => 'Kamer 12', 'description' => 'Biologie', 'instructions' => 'Hoeveel benen heeft een spin?', 'answer' => '8', 'alternative_answers' => ['acht']],
            ['name' => 'Kamer 13', 'description' => 'Geschiedenis', 'instructions' => 'Wie schilderde de Nachtwacht?', 'answer' => 'Rembrandt', 'alternative_answers' => ['Rembrandt van Rijn']],
            ['name' => 'Kamer 14', 'description' => 'Sport', 'instructions' => 'Om de hoeveel jaar worden de Olympische Zomerspelen gehouden?', 'answer' => '4', 'alternative_answers' => ['vier']],
            ['name' => 'Kamer 15', 'description' => 'Aardrijkskunde', 'instructions' => 'Wat is het kleinste land ter wereld?', 'answer' => 'Vaticaanstad'],
            ['name' => 'Kamer 16', 'description' => 'Taal', 'instructions' => 'Hoeveel letters heeft het Nederlandse alfabet?', 'answer' => '26', 'alternative_answers' => ['zesentwintig']],
            ['name' => 'Kamer 17', 'description' => 'Sterrenkunde', 'instructions' => 'Welke planeet staat bekend als de rode planeet?', 'answer' => 'Mars'],
        ];
    }
}
