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

    public const OCCUPYING = ['assigned', 'active', 'pending_review'];

    public const PLAYED = ['completed', 'failed', 'given_up'];

    public static function attemptsUsed(int $sessionId): int
    {
        return AnswerAttempt::count(['room_session_id' => $sessionId]);
    }

    /** De laatst afgeronde sessie van dit team waarvan de beoordelingsuitslag nog niet getoond is. */
    public static function unseenResultForTeam(int $teamId): ?array
    {
        $rows = self::where(['team_id' => $teamId, 'result_seen' => 0], 'id DESC');

        return $rows[0] ?? null;
    }

    public static function markResultSeen(int $sessionId): void
    {
        self::update($sessionId, ['result_seen' => 1]);
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
            'active' => ['completed', 'failed', 'given_up', 'pending_review'],
            'pending_review' => ['completed', 'active', 'failed'],
            default => [],
        };

        return in_array($to, $allowed, true);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'assigned' => 'Onderweg',
            'active' => 'Bezig',
            'pending_review' => 'Wacht op beoordeling',
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

    /** Foto-antwoorden die nog op een oordeel van de organisator wachten, oudste eerst. */
    public static function pendingReview(int $gameId): array
    {
        $sql = 'SELECT answer_attempts.* FROM answer_attempts
                JOIN room_sessions ON room_sessions.id = answer_attempts.room_session_id
                WHERE room_sessions.game_id = :game_id AND answer_attempts.review_status = :status
                ORDER BY answer_attempts.id ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['game_id' => $gameId, 'status' => 'pending']);

        return $stmt->fetchAll();
    }

    public static function pendingReviewCount(int $gameId): int
    {
        $sql = 'SELECT COUNT(*) AS c FROM answer_attempts
                JOIN room_sessions ON room_sessions.id = answer_attempts.room_session_id
                WHERE room_sessions.game_id = :game_id AND answer_attempts.review_status = :status';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['game_id' => $gameId, 'status' => 'pending']);

        return (int) $stmt->fetch()['c'];
    }
}

class ChatMessage extends Record
{
    protected static string $table = 'chat_messages';

    public static function forTeam(int $teamId): array
    {
        return self::where(['team_id' => $teamId], 'id ASC');
    }

    public static function unreadByAdminCount(int $gameId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS c FROM chat_messages WHERE game_id = :game_id AND sender = :sender AND read_by_admin_at IS NULL'
        );
        $stmt->execute(['game_id' => $gameId, 'sender' => 'team']);

        return (int) $stmt->fetch()['c'];
    }

    public static function unreadByAdminCountForTeam(int $teamId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS c FROM chat_messages WHERE team_id = :team_id AND sender = :sender AND read_by_admin_at IS NULL'
        );
        $stmt->execute(['team_id' => $teamId, 'sender' => 'team']);

        return (int) $stmt->fetch()['c'];
    }

    public static function unreadByTeamCount(int $teamId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) AS c FROM chat_messages WHERE team_id = :team_id AND sender = :sender AND read_by_team_at IS NULL'
        );
        $stmt->execute(['team_id' => $teamId, 'sender' => 'admin']);

        return (int) $stmt->fetch()['c'];
    }

    public static function markReadByAdmin(int $teamId): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE chat_messages SET read_by_admin_at = :now WHERE team_id = :team_id AND sender = 'team' AND read_by_admin_at IS NULL"
        );
        $stmt->execute(['now' => Database::now(), 'team_id' => $teamId]);
    }

    public static function markReadByTeam(int $teamId): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE chat_messages SET read_by_team_at = :now WHERE team_id = :team_id AND sender = 'admin' AND read_by_team_at IS NULL"
        );
        $stmt->execute(['now' => Database::now(), 'team_id' => $teamId]);
    }
}

class Setting extends Record
{
    protected static string $table = 'settings';

    public static function get(string $key, ?string $default = null): ?string
    {
        $stmt = Database::connection()->prepare('SELECT value FROM settings WHERE key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        return $row === false ? $default : $row['value'];
    }

    public static function set(string $key, ?string $value): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO settings (key, value, created_at, updated_at) VALUES (:key, :value, :now, :now)
             ON CONFLICT(key) DO UPDATE SET value = :value, updated_at = :now'
        );
        $stmt->execute(['key' => $key, 'value' => $value, 'now' => Database::now()]);
    }

    /** @return array<string, string|null> */
    public static function many(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = self::get($key);
        }

        return $result;
    }
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
