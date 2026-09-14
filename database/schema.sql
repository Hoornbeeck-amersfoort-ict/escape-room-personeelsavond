-- Plain-PHP escape room schema (SQLite). Ported 1:1 from the Laravel migrations.

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    created_at TEXT,
    updated_at TEXT
);

CREATE TABLE IF NOT EXISTS games (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    start_time TEXT,
    end_time TEXT,
    status TEXT NOT NULL DEFAULT 'draft',
    created_at TEXT,
    updated_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_games_status ON games(status);

CREATE TABLE IF NOT EXISTS teams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    game_id INTEGER NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    code_hash TEXT NOT NULL,
    code TEXT,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT,
    updated_at TEXT,
    UNIQUE(game_id, name)
);
CREATE INDEX IF NOT EXISTS idx_teams_active ON teams(active);

CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    game_id INTEGER NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    description TEXT,
    instructions TEXT,
    answer TEXT NOT NULL,
    alternative_answers TEXT, -- JSON array
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT,
    updated_at TEXT,
    UNIQUE(game_id, name)
);
CREATE INDEX IF NOT EXISTS idx_rooms_active ON rooms(active);

CREATE TABLE IF NOT EXISTS room_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    game_id INTEGER NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    team_id INTEGER NOT NULL REFERENCES teams(id) ON DELETE CASCADE,
    room_id INTEGER NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
    status TEXT NOT NULL DEFAULT 'assigned',
    started_at TEXT,
    finished_at TEXT,
    points INTEGER NOT NULL DEFAULT 0,
    created_at TEXT,
    updated_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_rs_game ON room_sessions(game_id);
CREATE INDEX IF NOT EXISTS idx_rs_room ON room_sessions(room_id);
CREATE INDEX IF NOT EXISTS idx_rs_status ON room_sessions(status);
CREATE INDEX IF NOT EXISTS idx_rs_team_status ON room_sessions(team_id, status);
CREATE INDEX IF NOT EXISTS idx_rs_room_status ON room_sessions(room_id, status);

CREATE TABLE IF NOT EXISTS answer_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_session_id INTEGER NOT NULL REFERENCES room_sessions(id) ON DELETE CASCADE,
    answer TEXT NOT NULL,
    correct INTEGER NOT NULL,
    attempt_number INTEGER NOT NULL,
    created_at TEXT,
    updated_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_aa_session ON answer_attempts(room_session_id);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action TEXT NOT NULL,
    target_type TEXT NOT NULL,
    target_id INTEGER,
    old_values TEXT,
    new_values TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_al_target ON audit_logs(target_type, target_id);
