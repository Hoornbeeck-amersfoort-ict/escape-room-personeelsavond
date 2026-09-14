<?php

namespace App\Enums;

enum GameStatus: string
{
    case Draft = 'draft';
    case Running = 'running';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Niet gestart',
            self::Running => 'Actief',
            self::Finished => 'Afgelopen',
        };
    }
}
