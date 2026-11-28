-- Actualiza una instalación previa de SumaRitmo v1.
-- Ejecuta una sola vez sobre la misma base de datos de la aplicación.

ALTER TABLE users
    ADD COLUMN age_range VARCHAR(16) NULL AFTER password_hash,
    ADD COLUMN adult_confirmed_at DATETIME NULL AFTER age_range,
    ADD COLUMN goal_focus VARCHAR(24) NOT NULL DEFAULT 'bienestar' AFTER adult_confirmed_at;

ALTER TABLE daily_entries
    ADD COLUMN effort_rating TINYINT UNSIGNED NULL AFTER session_detail,
    ADD COLUMN body_feeling VARCHAR(40) NULL AFTER effort_rating;
