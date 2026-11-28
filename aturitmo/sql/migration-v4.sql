-- Añade registros opcionales de consumo a una instalación SumaRitmo v3.
-- Ejecuta una sola vez en phpMyAdmin, con la base de datos admin_aturitmo seleccionada.

ALTER TABLE daily_entries
    ADD COLUMN consumption_status VARCHAR(20) NULL AFTER body_feeling,
    ADD COLUMN consumption_timing VARCHAR(20) NULL AFTER consumption_status,
    ADD COLUMN alcohol_drinks TINYINT UNSIGNED NULL AFTER consumption_timing,
    ADD COLUMN consumption_consent_version VARCHAR(24) NULL AFTER alcohol_drinks;
