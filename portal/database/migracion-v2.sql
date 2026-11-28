-- Actualización v2 (28 nov 2026): tiempos por corredor y ranking por tiempo propio.
-- El portal la aplica solo la primera vez que se abre. Ejecútela a mano solo si
-- el portal pide hacerlo (cuando el usuario de la base no tiene permiso ALTER).
ALTER TABLE usuarios ADD COLUMN ajustes_juego TEXT NULL;
ALTER TABLE intentos ADD COLUMN tiempo_propio_ms INT UNSIGNED NULL AFTER tiempo_total_ms;
