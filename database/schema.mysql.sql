-- Plain-PHP escape room schema (MySQL/MariaDB). Zelfde tabellen en kolommen als
-- schema.sql (SQLite), alleen in MySQL-dialect: AUTO_INCREMENT in plaats van
-- AUTOINCREMENT, TINYINT voor de vlaggen, DATETIME voor de tijdstempels en
-- InnoDB zodat de foreign keys en transacties echt werken.

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS games (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    start_time DATETIME NULL,
    end_time DATETIME NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'draft',
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_games_status (status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teams (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    game_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    code VARCHAR(32) NULL,
    active TINYINT NOT NULL DEFAULT 1,
    -- Eén teamleider tegelijk: de sessie die het team nu bezet houdt.
    session_id VARCHAR(128) NULL,
    session_seen_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_teams_game_name (game_id, name),
    INDEX idx_teams_active (active),
    CONSTRAINT fk_teams_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rooms (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    game_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    instructions MEDIUMTEXT NULL,
    route_instructions MEDIUMTEXT NULL,       -- opdracht op het "ga naar deze kamer"-scherm (HTML)
    answer VARCHAR(255) NOT NULL,
    alternative_answers TEXT NULL,            -- JSON array
    image_path VARCHAR(255) NULL,             -- oude losse afbeelding; staat nu in de inhoud zelf
    exclusive TINYINT NOT NULL DEFAULT 0,     -- 1 = er mag maar één team tegelijk in
    active TINYINT NOT NULL DEFAULT 1,
    allow_image_answer TINYINT NOT NULL DEFAULT 0, -- 1 = team mag een foto als antwoord insturen (moet beoordeeld worden)
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_rooms_game_name (game_id, name),
    INDEX idx_rooms_active (active),
    CONSTRAINT fk_rooms_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_sessions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    game_id INT UNSIGNED NOT NULL,
    team_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'assigned',
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    points INT NOT NULL DEFAULT 0,
    result_seen TINYINT NOT NULL DEFAULT 1, -- 0 = team heeft de uitslag van een door de admin beoordeeld antwoord nog niet gezien
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_rs_game (game_id),
    INDEX idx_rs_room (room_id),
    INDEX idx_rs_status (status),
    INDEX idx_rs_team_status (team_id, status),
    INDEX idx_rs_room_status (room_id, status),
    CONSTRAINT fk_rs_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    CONSTRAINT fk_rs_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_rs_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS answer_attempts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    room_session_id INT UNSIGNED NOT NULL,
    answer TEXT NOT NULL,
    correct TINYINT NOT NULL,
    attempt_number INT NOT NULL,
    image_path VARCHAR(255) NULL,                  -- gevuld als dit een foto-antwoord is
    review_status VARCHAR(32) NOT NULL DEFAULT 'auto', -- auto | pending | approved | rejected
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    acknowledged TINYINT NOT NULL DEFAULT 1,       -- 0 = team heeft de afkeuring nog niet gezien
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_aa_session (room_session_id),
    INDEX idx_aa_review_status (review_status),
    CONSTRAINT fk_aa_session FOREIGN KEY (room_session_id) REFERENCES room_sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_aa_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    game_id INT UNSIGNED NOT NULL,
    team_id INT UNSIGNED NOT NULL,
    sender VARCHAR(16) NOT NULL, -- 'team' | 'admin'
    body TEXT NOT NULL,
    read_by_admin_at DATETIME NULL,
    read_by_team_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_chat_team (team_id),
    INDEX idx_chat_game (game_id),
    CONSTRAINT fk_chat_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- `key` is een gereserveerd woord in MySQL; de code quote de kolom daarom altijd.
CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(191) NOT NULL PRIMARY KEY,
    value TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NULL,
    action VARCHAR(255) NOT NULL,
    target_type VARCHAR(255) NOT NULL,
    target_id INT UNSIGNED NULL,
    old_values TEXT NULL,
    new_values TEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_al_target (target_type, target_id),
    CONSTRAINT fk_al_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
