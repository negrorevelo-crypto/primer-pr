-- Nivel 1 de pago en modo piloto, datos de captura asistida y perfil deportivo.
-- Ejecuta una sola vez sobre la base de datos existente, después de migration-v5.sql.

ALTER TABLE users
    ADD COLUMN preferred_sports VARCHAR(255) NULL AFTER weekly_activity_goal_minutes,
    ADD COLUMN available_days_per_week TINYINT UNSIGNED NULL AFTER preferred_sports,
    ADD COLUMN preferred_session_minutes SMALLINT UNSIGNED NULL AFTER available_days_per_week,
    ADD COLUMN subscription_plan VARCHAR(32) NOT NULL DEFAULT 'nivel_1' AFTER goal_focus,
    ADD COLUMN subscription_status VARCHAR(16) NOT NULL DEFAULT 'pilot' AFTER subscription_plan,
    ADD COLUMN billing_provider VARCHAR(32) NULL AFTER subscription_status,
    ADD COLUMN billing_subscription_ref VARCHAR(128) NULL AFTER billing_provider;

ALTER TABLE daily_entries
    ADD COLUMN distance_km DECIMAL(8,2) NULL AFTER session_detail,
    ADD COLUMN average_heart_rate SMALLINT UNSIGNED NULL AFTER distance_km,
    ADD COLUMN maximum_heart_rate SMALLINT UNSIGNED NULL AFTER average_heart_rate,
    ADD COLUMN calories_burned SMALLINT UNSIGNED NULL AFTER maximum_heart_rate,
    ADD COLUMN sleep_score TINYINT UNSIGNED NULL AFTER sleep_hours,
    ADD COLUMN hrv_ms DECIMAL(7,2) NULL AFTER sleep_score,
    ADD COLUMN source_type VARCHAR(16) NOT NULL DEFAULT 'manual' AFTER hrv_ms,
    ADD COLUMN source_consent_version VARCHAR(24) NULL AFTER source_type;
