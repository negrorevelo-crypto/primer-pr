-- Create the database and user in Plesk first, then select that database and run this file.
-- Keep the database credentials outside the public web root.

CREATE TABLE quote_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_code VARCHAR(24) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    property_type VARCHAR(32) NOT NULL,
    project_timeline VARCHAR(32) NOT NULL,
    scope_description TEXT NOT NULL,
    preferred_contact VARCHAR(20) NOT NULL,
    consent_at DATETIME NOT NULL,
    status VARCHAR(24) NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_quote_request_code (request_code),
    KEY idx_quote_requests_status_created (status, created_at),
    KEY idx_quote_requests_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quote_request_services (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quote_request_id BIGINT UNSIGNED NOT NULL,
    service_key VARCHAR(40) NOT NULL,
    service_label VARCHAR(80) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_quote_request_services_request (quote_request_id),
    CONSTRAINT fk_quote_request_services_request
        FOREIGN KEY (quote_request_id) REFERENCES quote_requests(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

