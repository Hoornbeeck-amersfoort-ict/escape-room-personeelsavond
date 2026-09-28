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
    -- Eén teamleider tegelijk: de sessie die het team nu bezet houdt.
    session_id TEXT,
    session_seen_at TEXT,
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
    route_instructions TEXT,  -- opdracht op het "ga naar deze kamer"-scherm (HTML)
    answer TEXT NOT NULL,
    alternative_answers TEXT, -- JSON array
    image_path TEXT,          -- oude losse afbeelding; staat nu in de inhoud zelf
    exclusive INTEGER NOT NULL DEFAULT 0, -- 1 = er mag maar één team tegelijk in
    active INTEGER NOT NULL DEFAULT 1,
    allow_image_answer INTEGER NOT NULL DEFAULT 0, -- 1 = team mag een foto als antwoord insturen (moet beoordeeld worden)
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
    result_seen INTEGER NOT NULL DEFAULT 1, -- 0 = team heeft de uitslag van een door de admin beoordeeld antwoord nog niet gezien
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
    image_path TEXT,                          -- gevuld als dit een foto-antwoord is
    review_status TEXT NOT NULL DEFAULT 'auto', -- auto | pending | approved | rejected
    reviewed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    reviewed_at TEXT,
    acknowledged INTEGER NOT NULL DEFAULT 1,   -- 0 = team heeft de afkeuring nog niet gezien
    created_at TEXT,
    updated_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_aa_session ON answer_attempts(room_session_id);
CREATE INDEX IF NOT EXISTS idx_aa_review_status ON answer_attempts(review_status);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    game_id INTEGER NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    team_id INTEGER NOT NULL REFERENCES teams(id) ON DELETE CASCADE,
    sender TEXT NOT NULL, -- 'team' | 'admin'
    body TEXT NOT NULL,
    read_by_admin_at TEXT,
    read_by_team_at TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE INDEX IF NOT EXISTS idx_chat_team ON chat_messages(team_id);
CREATE INDEX IF NOT EXISTS idx_chat_game ON chat_messages(game_id);

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    created_at TEXT,
    updated_at TEXT
);

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
