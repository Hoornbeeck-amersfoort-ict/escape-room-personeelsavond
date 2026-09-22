<?php

namespace App;

class User extends Record
{
    protected static string $table = 'users';
}

class Game extends Record
{
    protected static string $table = 'games';

    public static function hasReachedEndTime(array $game, ?string $now = null): bool
    {
        $now ??= Database::now();

        return $game['end_time'] !== null && $now >= $game['end_time'];
    }

    public static function isRunning(array $game): bool
    {
        return $game['status'] === 'running';
    }

    public static function isAcceptingPlay(array $game): bool
    {
        return self::isRunning($game) && ! self::hasReachedEndTime($game);
    }
}

class Team extends Record
{
    protected static string $table = 'teams';

    /** Zo lang mag de teamleider wegvallen (tabblad dicht, telefoon leeg) voor de plek vrijkomt. */
    public const SEAT_TIMEOUT_SECONDS = 90;

    /** Houdt een ander apparaat dit team op dit moment bezet? */
    public static function seatTakenByOther(array $team, string $sessionId): bool
    {
        if ($team['session_id'] === null || $team['session_id'] === $sessionId) {
            return false;
        }

        if ($team['session_seen_at'] === null) {
            return false;
        }

        return strtotime($team['session_seen_at']) > time() - self::SEAT_TIMEOUT_SECONDS;
    }

    public static function claimSeat(int $teamId, string $sessionId): void
    {
        self::update($teamId, ['session_id' => $sessionId, 'session_seen_at' => Database::now()]);
    }

    public static function releaseSeat(int $teamId): void
    {
        self::update($teamId, ['session_id' => null, 'session_seen_at' => null]);
    }

    /** Hartslag van de teamleider; schrijft hooguit eens per 15 seconden. */
    public static function touchSeat(array $team): void
    {
        $laatst = $team['session_seen_at'] ? strtotime($team['session_seen_at']) : 0;

        if ($laatst > time() - 15) {
            return;
        }

        self::update((int) $team['id'], ['session_seen_at' => Database::now()]);
    }

    public static function setCode(int $teamId, string $plainCode): void
    {
        self::update($teamId, [
            'code' => $plainCode,
            'code_hash' => password_hash($plainCode, PASSWORD_DEFAULT),
        ]);
    }

    public static function verifyCode(array $team, string $plainCode): bool
    {
        return password_verify($plainCode, $team['code_hash']);
    }

    /** Room IDs this team has already completed, failed, or given up. */
    public static function playedRoomIds(int $teamId): array
    {
        $sessions = RoomSession::where(['team_id' => $teamId, 'status' => RoomSession::PLAYED]);

        return array_map(fn ($s) => (int) $s['room_id'], $sessions);
    }

    /** The team's current unfinished room session (assigned or active), if any. */
    public static function currentRoomSession(int $teamId): ?array
    {
        $sessions = RoomSession::where(['team_id' => $teamId, 'status' => RoomSession::OCCUPYING], 'id DESC');

        return $sessions[0] ?? null;
    }
}

class Room extends Record
{
    protected static string $table = 'rooms';

    public static function allAnswers(array $room): array
    {
        $alternatives = $room['alternative_answers'] ? json_decode($room['alternative_answers'], true) : [];

        return array_merge([$room['answer']], $alternatives ?: []);
    }

    public static function activeTeamsCount(int $roomId): int
    {
        return RoomSession::count(['room_id' => $roomId, 'status' => RoomSession::OCCUPYING]);
    }

    /** Een exclusieve kamer is vol zodra er één team in zit. */
    public static function isFull(array $room): bool
    {
        return (int) $room['exclusive'] === 1 && self::activeTeamsCount((int) $room['id']) > 0;
    }
}

class RoomSession extends Record
{
    protected static string $table = 'room_sessions';

    public const OCCUPYING = ['assigned', 'active'];

    public const PLAYED = ['completed', 'failed', 'given_up'];

    public static function attemptsUsed(int $sessionId): int
    {
        return AnswerAttempt::count(['room_session_id' => $sessionId]);
    }

    public static function activeDurationSeconds(array $session, ?string $now = null): int
    {
        if ($session['started_at'] === null) {
            return 0;
        }

        $end = $session['finished_at'] ?? ($now ?? Database::now());

        return max(0, strtotime($end) - strtotime($session['started_at']));
    }

    public static function canTransitionTo(string $from, string $to): bool
    {
        $allowed = match ($from) {
            'assigned' => ['active', 'given_up', 'failed'],
            'active' => ['completed', 'failed', 'given_up'],
            default => [],
        };

        return in_array($to, $allowed, true);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'assigned' => 'Onderweg',
            'active' => 'Bezig',
            'completed' => 'Opgelost',
            'failed' => 'Verloren',
            'given_up' => 'Opgegeven',
            default => $status,
        };
    }
}

class AnswerAttempt extends Record
{
    protected static string $table = 'answer_attempts';
}

class AuditLog extends Record
{
    protected static string $table = 'audit_logs';

    public static function log(int $adminId, string $action, string $targetType, ?int $targetId, array $old = [], array $new = []): void
    {
        self::insert([
            'admin_id' => $adminId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'old_values' => json_encode($old),
            'new_values' => json_encode($new),
        ]);
    }
}
