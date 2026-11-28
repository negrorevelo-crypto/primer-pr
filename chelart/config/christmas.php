<?php
declare(strict_types=1);

/**
 * Campaña de temporada: Christmas Decoration Service (Black Friday 2026).
 *
 * Todo lo que el sitio necesita saber de la campaña está aquí: interruptor,
 * fechas de la promoción, catálogos bilingües y estados del módulo administrativo.
 * Ajustes opcionales (variables de entorno o config/plesk-secrets.php):
 *   CHRISTMAS_CAMPAIGN_ENABLED  '1' (por omisión) muestra la página y recibe solicitudes; '0' la oculta.
 *   CHRISTMAS_PROMO_DEADLINE    Fecha (America/New_York) desde la que ya no aplica el descuento. Por omisión 2026-12-01.
 *   CHRISTMAS_CAMPAIGN_CODE     Código guardado en campaign_source mientras rige la promoción. Por omisión BLACK_FRIDAY_2026.
 *   CHRISTMAS_DISCOUNT_PERCENT  Porcentaje de la promoción. Por omisión 15.
 *   CHRISTMAS_NOTIFY_EMAIL      Buzón para los avisos; si está vacío se usa QUOTE_NOTIFY_EMAIL.
 *   CHRISTMAS_UPLOAD_DIR        Carpeta privada de fotos; por omisión storage/christmas-uploads (fuera de public/).
 * Desactivar la campaña no borra solicitudes ni fotos: el módulo /admin/christmas.php sigue disponible.
 */

const CHRISTMAS_TIMEZONE = 'America/New_York';
const CHRISTMAS_PAGE_PATH = 'christmas-decoration-services/';
const CHRISTMAS_MAX_PHOTOS = 6;
const CHRISTMAS_MAX_PHOTO_BYTES = 8 * 1024 * 1024;
const CHRISTMAS_MAX_PHOTO_PIXELS = 40_000_000;

const CHRISTMAS_PACKAGES = [
    'essential' => ['en' => 'Essential Christmas', 'es' => 'Navidad Esencial'],
    'premium' => ['en' => 'Premium Christmas', 'es' => 'Navidad Premium'],
    'luxury' => ['en' => 'Luxury Christmas', 'es' => 'Navidad de Lujo'],
    'custom' => ['en' => 'Custom Service', 'es' => 'Servicio personalizado'],
];

/** Contenido de cada tarjeta de paquete (sin precios: se cotizan individualmente). */
const CHRISTMAS_PACKAGE_FEATURES = [
    'essential' => ['Exterior lights installation.', 'Roofline decoration.', 'Post-Christmas removal.'],
    'premium' => ['Full exterior decoration.', 'Facade and garden decoration.', 'Installation and removal.', 'Seasonal maintenance.'],
    'luxury' => ['Custom decoration design.', 'Complete interior and exterior decoration.', 'Premium lighting installation.', 'Seasonal maintenance.', 'Post-holiday removal.'],
];

const CHRISTMAS_CONTACT_METHODS = [
    'phone' => ['en' => 'Phone', 'es' => 'Teléfono'],
    'whatsapp' => ['en' => 'WhatsApp', 'es' => 'WhatsApp'],
    'email' => ['en' => 'Email', 'es' => 'Correo electrónico'],
];

const CHRISTMAS_PROPERTY_TYPES = [
    'single_family' => ['en' => 'Single Family Home', 'es' => 'Casa unifamiliar'],
    'townhouse' => ['en' => 'Townhouse', 'es' => 'Townhouse'],
    'condo' => ['en' => 'Condo', 'es' => 'Condominio'],
    'commercial' => ['en' => 'Commercial Property', 'es' => 'Propiedad comercial'],
    'other' => ['en' => 'Other', 'es' => 'Otro'],
];

const CHRISTMAS_DECORATION_AREAS = [
    'roofline' => ['en' => 'Roofline', 'es' => 'Línea del techo'],
    'front_entrance' => ['en' => 'Front Entrance', 'es' => 'Entrada principal'],
    'windows' => ['en' => 'Windows', 'es' => 'Ventanas'],
    'palm_trees' => ['en' => 'Palm Trees', 'es' => 'Palmeras'],
    'garden' => ['en' => 'Garden and Landscaping', 'es' => 'Jardín y paisajismo'],
    'indoor' => ['en' => 'Indoor Decoration', 'es' => 'Decoración interior'],
    'entire_property' => ['en' => 'Entire Property', 'es' => 'Toda la propiedad'],
];

const CHRISTMAS_SUPPLIERS = [
    'customer' => ['en' => 'Customer Supplies Decorations', 'es' => 'El cliente aporta la decoración'],
    'chelart' => ['en' => 'Chelart Supplies Decorations', 'es' => 'Chelart aporta la decoración'],
    'assistance' => ['en' => 'Need Assistance', 'es' => 'Necesito asesoría'],
];

const CHRISTMAS_PROPERTY_SIZES = [
    'small' => ['en' => 'Small', 'es' => 'Pequeña'],
    'medium' => ['en' => 'Medium', 'es' => 'Mediana'],
    'large' => ['en' => 'Large', 'es' => 'Grande'],
    'custom' => ['en' => 'Custom', 'es' => 'Personalizada'],
];

/** Estados del flujo comercial, en el orden en que avanza una solicitud. */
const CHRISTMAS_STATUSES = [
    'NEW_REQUEST' => 'New request',
    'CONTACTED' => 'Contacted',
    'SITE_VISIT_SCHEDULED' => 'Site visit scheduled',
    'QUOTE_SENT' => 'Quote sent',
    'APPROVED' => 'Approved',
    'SCHEDULED' => 'Scheduled',
    'COMPLETED' => 'Completed',
    'CANCELLED' => 'Cancelled',
];

/** Tipos de imagen aceptados: MIME detectado => extensión con que se guarda. */
const CHRISTMAS_PHOTO_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

function christmas_campaign_enabled(): bool
{
    return !in_array(strtolower(trim(chelart_setting('CHRISTMAS_CAMPAIGN_ENABLED', '1'))), ['0', 'false', 'off', 'no'], true);
}

function christmas_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone(CHRISTMAS_TIMEZONE));
}

function christmas_promo_deadline(): DateTimeImmutable
{
    $zone = new DateTimeZone(CHRISTMAS_TIMEZONE);
    $configured = trim(chelart_setting('CHRISTMAS_PROMO_DEADLINE', '2026-12-01'));
    $deadline = DateTimeImmutable::createFromFormat('!Y-m-d', $configured, $zone);
    return $deadline ?: new DateTimeImmutable('2026-12-01 00:00:00', $zone);
}

/** La promoción aplica a solicitudes enviadas antes del 1 de diciembre (hora de Florida). */
function christmas_promo_active(?DateTimeImmutable $at = null): bool
{
    return christmas_campaign_enabled() && ($at ?? christmas_now()) < christmas_promo_deadline();
}

function christmas_discount_percent(): int
{
    $value = (int)chelart_setting('CHRISTMAS_DISCOUNT_PERCENT', '15');
    return max(0, min(90, $value));
}

/** campaign_source y discount_percentage que se guardan con cada solicitud. */
function christmas_campaign_values(?DateTimeImmutable $at = null): array
{
    if (christmas_promo_active($at)) {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', chelart_setting('CHRISTMAS_CAMPAIGN_CODE', 'BLACK_FRIDAY_2026')) ?: 'BLACK_FRIDAY_2026');
        return ['campaign_source' => substr($code, 0, 40), 'discount_percentage' => christmas_discount_percent()];
    }
    $year = ($at ?? christmas_now())->format('Y');
    return ['campaign_source' => 'CHRISTMAS_' . $year, 'discount_percentage' => 0];
}

function christmas_upload_dir(): string
{
    $configured = trim(chelart_setting('CHRISTMAS_UPLOAD_DIR'));
    return rtrim($configured !== '' ? $configured : dirname(__DIR__) . '/storage/christmas-uploads', '/\\');
}

function christmas_label(array $catalog, ?string $key, string $language = 'en'): string
{
    if ($key === null || !isset($catalog[$key])) {
        return (string)$key;
    }
    $entry = $catalog[$key];
    return is_array($entry) ? ($entry[$language] ?? $entry['en']) : (string)$entry;
}

function christmas_notify_email(): string
{
    $configured = trim(chelart_setting('CHRISTMAS_NOTIFY_EMAIL'));
    return $configured !== '' ? $configured : chelart_setting('QUOTE_NOTIFY_EMAIL', 'chelartusa@gmail.com');
}

/** URL pública absoluta del sitio (para correos y datos estructurados). */
function christmas_public_url(string $path = ''): string
{
    return 'https://' . CHELART_CANONICAL_HOST . '/' . ltrim($path, '/');
}
