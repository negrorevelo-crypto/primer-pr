-- Crea primero la base de datos y un usuario de aplicación en Plesk.
-- Ejecuta este archivo seleccionando esa base de datos. Las credenciales
-- deben permanecer en config/plesk-secrets.php, fuera de public/.

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    display_name VARCHAR(80) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    age_range VARCHAR(16) NULL,
    adult_confirmed_at DATETIME NULL,
    terms_accepted_at DATETIME NULL,
    terms_version VARCHAR(24) NULL,
    goal_focus VARCHAR(24) NOT NULL DEFAULT 'bienestar',
    weekly_activity_goal_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 150,
    preferred_sports VARCHAR(255) NULL,
    available_days_per_week TINYINT UNSIGNED NULL,
    preferred_session_minutes SMALLINT UNSIGNED NULL,
    subscription_plan VARCHAR(32) NOT NULL DEFAULT 'nivel_1',
    subscription_status VARCHAR(16) NOT NULL DEFAULT 'pilot',
    billing_provider VARCHAR(32) NULL,
    billing_subscription_ref VARCHAR(128) NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'America/Bogota',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE daily_entries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    entry_type VARCHAR(16) NOT NULL,
    sport_name VARCHAR(48) NULL,
    duration_minutes SMALLINT UNSIGNED NULL,
    session_detail VARCHAR(160) NULL,
    distance_km DECIMAL(8,2) NULL,
    average_heart_rate SMALLINT UNSIGNED NULL,
    maximum_heart_rate SMALLINT UNSIGNED NULL,
    calories_burned SMALLINT UNSIGNED NULL,
    effort_rating TINYINT UNSIGNED NULL,
    body_feeling VARCHAR(40) NULL,
    consumption_status VARCHAR(20) NULL,
    consumption_timing VARCHAR(20) NULL,
    alcohol_drinks TINYINT UNSIGNED NULL,
    consumption_consent_version VARCHAR(24) NULL,
    sleep_hours DECIMAL(4,2) NULL,
    sleep_score TINYINT UNSIGNED NULL,
    hrv_ms DECIMAL(7,2) NULL,
    source_type VARCHAR(16) NOT NULL DEFAULT 'manual',
    source_consent_version VARCHAR(24) NULL,
    source_record_key CHAR(64) NULL,
    meal_category VARCHAR(32) NULL,
    content TEXT NULL,
    mood VARCHAR(32) NULL,
    occurred_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_entries_user_occurred (user_id, occurred_at),
    KEY idx_entries_user_type_occurred (user_id, entry_type, occurred_at),
    UNIQUE KEY uq_entries_user_source_record (user_id, source_record_key),
    CONSTRAINT fk_entries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE weight_entries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    weight_kg DECIMAL(5,2) NOT NULL,
    measured_on DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_weight_user_measured (user_id, measured_on, id),
    CONSTRAINT fk_weight_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
