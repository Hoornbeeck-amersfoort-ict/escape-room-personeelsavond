<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a requested action no longer matches reality on the server —
 * e.g. a stale browser tab tries to start a room that was already completed
 * on another device, or the game ended between page load and submit. The UI
 * treats this as "refresh and show the current state" rather than a hard error.
 */
class RoomSessionStateException extends RuntimeException {}
