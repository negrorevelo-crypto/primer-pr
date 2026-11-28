-- Deduplicación de actividades importadas desde archivos Garmin CSV.
-- Ejecuta una sola vez después de migration-v6.sql.

ALTER TABLE daily_entries
    ADD COLUMN source_record_key CHAR(64) NULL AFTER source_consent_version,
    ADD UNIQUE KEY uq_entries_user_source_record (user_id, source_record_key);
