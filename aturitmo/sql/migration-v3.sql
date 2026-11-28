-- Añade el registro de aceptación del acuerdo de uso a una instalación v2.
-- Ejecuta una sola vez en phpMyAdmin, con la base de datos admin_aturitmo seleccionada.

ALTER TABLE users
    ADD COLUMN terms_accepted_at DATETIME NULL AFTER adult_confirmed_at,
    ADD COLUMN terms_version VARCHAR(24) NULL AFTER terms_accepted_at;
