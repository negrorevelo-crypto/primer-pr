-- Campaña CHRISTMAS DECORATION SERVICE (Black Friday 2026) — migración aditiva.
--
-- ANTES DE EJECUTAR EN PLESK: respalda la base (phpMyAdmin > Exportar > Rápido > SQL,
-- o "Databases > Export Dump" en Plesk). Este archivo solo CREA tablas nuevas;
-- no modifica ni borra quote_requests ni quote_request_services.
-- Es seguro ejecutarlo más de una vez (IF NOT EXISTS).
--
-- Por qué tablas propias: quote_requests exige campos que este formulario no usa
-- (project_timeline, scope_description) y no tiene dirección, fechas, paquete,
-- cotización ni notas. Ampliarla obligaría a tocar el formulario general.
-- La integración con el sistema actual se hace por código: mismo login de /admin/,
-- mismo correo del hosting, misma protección CSRF y referencia con prefijo XMAS-.

CREATE TABLE IF NOT EXISTS christmas_quote_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_number VARCHAR(24) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    contact_method VARCHAR(16) NOT NULL,
    property_address VARCHAR(200) NOT NULL,
    city VARCHAR(100) NOT NULL,
    zip_code VARCHAR(10) NOT NULL,
    property_type VARCHAR(24) NOT NULL,
    package_type VARCHAR(16) NOT NULL,
    decoration_areas VARCHAR(255) NOT NULL COMMENT 'Claves separadas por coma: roofline,front_entrance,windows,palm_trees,garden,indoor,entire_property',
    decoration_supplier VARCHAR(16) NOT NULL,
    property_size VARCHAR(16) NOT NULL,
    installation_date DATE NULL,
    removal_date DATE NULL,
    additional_details TEXT NULL,
    uploaded_photos SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cantidad de fotos; los archivos están en christmas_quote_photos',
    privacy_consent TINYINT(1) NOT NULL DEFAULT 0,
    consent_at DATETIME NOT NULL,
    language CHAR(2) NOT NULL DEFAULT 'en',
    campaign_source VARCHAR(40) NOT NULL DEFAULT 'BLACK_FRIDAY_2026',
    discount_percentage DECIMAL(5,2) NOT NULL DEFAULT 15.00,
    estimated_price DECIMAL(10,2) NULL COMMENT 'Uso interno; nunca se publica ni se envía automáticamente',
    final_quote DECIMAL(10,2) NULL COMMENT 'Valor revisado por el equipo de Chelart',
    request_status VARCHAR(24) NOT NULL DEFAULT 'NEW_REQUEST',
    internal_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_christmas_request_number (request_number),
    KEY idx_christmas_status_created (request_status, created_at),
    KEY idx_christmas_package (package_type),
    KEY idx_christmas_installation (installation_date),
    KEY idx_christmas_email (email),
    KEY idx_christmas_city (city),
    KEY idx_christmas_campaign (campaign_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fotos adjuntas. El archivo vive fuera de la raíz web (storage/christmas-uploads)
-- con un nombre aleatorio; aquí solo se guarda la referencia.
CREATE TABLE IF NOT EXISTS christmas_quote_photos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id BIGINT UNSIGNED NOT NULL,
    stored_name VARCHAR(64) NOT NULL,
    original_name VARCHAR(160) NOT NULL,
    mime_type VARCHAR(32) NOT NULL,
    size_bytes INT UNSIGNED NOT NULL,
    width SMALLINT UNSIGNED NULL,
    height SMALLINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_christmas_photo_stored (stored_name),
    KEY idx_christmas_photos_request (request_id),
    CONSTRAINT fk_christmas_photos_request
        FOREIGN KEY (request_id) REFERENCES christmas_quote_requests(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
