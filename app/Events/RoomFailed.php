<?php

namespace App\Events;

use App\Models\RoomSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(public RoomSession $roomSession) {}
}
