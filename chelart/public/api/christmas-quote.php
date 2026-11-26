<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/notifications.php';
require_once dirname(__DIR__, 2) . '/config/christmas.php';
require_once dirname(__DIR__, 2) . '/config/christmas_notifications.php';

$language = (($_POST['language'] ?? '') === 'es') ? 'es' : 'en';

const CHRISTMAS_ERRORS = [
    'session' => ['Your session expired. Please return to the form and try again.', 'Tu sesión venció. Vuelve al formulario e inténtalo de nuevo.'],
    'closed' => ['Christmas bookings are closed right now. Please call (689) 250-9674 and we’ll help you.', 'Las reservas de Navidad están cerradas por ahora. Llámanos al (689) 250-9674 y te ayudamos.'],
    'wait' => ['Please wait a moment before sending another request.', 'Espera un momento antes de enviar otra solicitud.'],
    'limit' => ['We already received several requests from this email today. Our team will contact you soon; call (689) 250-9674 if you need to change something.', 'Ya recibimos varias solicitudes con este correo hoy. Te contactaremos pronto; llama al (689) 250-9674 si necesitas cambiar algo.'],
    'full_name' => ['Enter your full name.', 'Escribe tu nombre completo.'],
    'email' => ['Enter a valid email address, e.g. name@example.com.', 'Escribe un correo válido, p. ej. nombre@ejemplo.com.'],
    'phone' => ['Enter a valid phone number with area code, e.g. (407) 555-0123.', 'Escribe un teléfono válido con código de área, p. ej. (407) 555-0123.'],
    'contact_method' => ['Choose how you’d like us to contact you.', 'Elige cómo prefieres que te contactemos.'],
    'property_address' => ['Enter the property address.', 'Escribe la dirección de la propiedad.'],
    'city' => ['Enter the city.', 'Escribe la ciudad.'],
    'zip_code' => ['Enter a 5-digit ZIP code.', 'Escribe un código postal de 5 dígitos.'],
    'property_type' => ['Choose the property type.', 'Elige el tipo de propiedad.'],
    'package_type' => ['Choose a package.', 'Elige un paquete.'],
    'decoration_areas' => ['Choose at least one area to decorate.', 'Elige al menos un área para decorar.'],
    'decoration_supplier' => ['Tell us who supplies the decorations.', 'Indica quién aporta la decoración.'],
    'property_size' => ['Choose the approximate property size.', 'Elige el tamaño aproximado de la propiedad.'],
    'installation_date' => ['Choose an installation date from today onward.', 'Elige una fecha de instalación desde hoy en adelante.'],
    'removal_date' => ['The removal date must be after the installation date.', 'La fecha de retiro debe ser posterior a la de instalación.'],
    'additional_details' => ['Additional details can have up to 3,000 characters.', 'Los detalles adicionales admiten hasta 3.000 caracteres.'],
    'privacy_consent' => ['Accept the privacy notice and contact authorization to continue.', 'Acepta el aviso de privacidad y la autorización de contacto para continuar.'],
    'photos_count' => ['You can attach up to 6 photos.', 'Puedes adjuntar hasta 6 fotos.'],
    'photos_size' => ['Each photo must be 8 MB or smaller.', 'Cada foto debe pesar 8 MB o menos.'],
    'photos_type' => ['Photos must be JPG, PNG or WebP images.', 'Las fotos deben ser imágenes JPG, PNG o WebP.'],
    'photos_failed' => ['One of the photos could not be uploaded. Please try again or send it without photos.', 'Una de las fotos no se pudo subir. Inténtalo de nuevo o envía la solicitud sin fotos.'],
    'too_large' => ['The photos are too large to send together. Try fewer or smaller photos.', 'Las fotos son demasiado pesadas para enviarlas juntas. Prueba con menos fotos o más livianas.'],
    'server' => ['We couldn’t save your request. Please call (689) 250-9674 or email chelartusa@gmail.com and we’ll help you directly.', 'No pudimos guardar tu solicitud. Llámanos al (689) 250-9674 o escribe a chelartusa@gmail.com y te atenderemos directamente.'],
];

function christmas_fail(int $status, array $errorKeys, string $language): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    $es = $language === 'es';
    $items = '';
    foreach (array_unique($errorKeys) as $key) {
        $items .= '<li>' . h(CHRISTMAS_ERRORS[$key][$es ? 1 : 0] ?? $key) . '</li>';
    }
    $form = CHRISTMAS_PAGE_PATH . '#christmas-quote';
    echo '<!doctype html><html lang="' . ($es ? 'es' : 'en') . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><base href="' . h(app_base_path()) . '">'
        . '<title>' . ($es ? 'Solicitud no enviada' : 'Request not sent') . ' | Chelart</title><link rel="stylesheet" href="assets/css/site.css?v=20261126"><link rel="stylesheet" href="assets/css/christmas.css?v=20261127"></head>'
        . '<body class="thanks-page"><main class="thanks-card xmas-error-card"><p class="eyebrow">' . ($es ? 'Solicitud no enviada' : 'Request not sent') . '</p>'
        . '<h1>' . ($es ? 'Revisa estos datos.' : 'Please review these details.') . '</h1><ul class="xmas-error-list">' . $items . '</ul>'
        . '<a class="button button-primary" href="' . h($form) . '" onclick="if(history.length>1){history.back();return false;}">' . ($es ? 'Volver al formulario' : 'Back to the form') . ' <span aria-hidden="true">←</span></a></main></body></html>';
    exit;
}

function christmas_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

/** Quita caracteres de control (salvo saltos de línea en textos largos). */
function christmas_clean(string $value, bool $multiline = false): string
{
    $value = trim($value);
    $pattern = $multiline ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u';
    return (string)preg_replace($pattern, '', $value);
}

function christmas_post_choice(string $name, array $catalog): ?string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) && isset($catalog[$value]) ? $value : null;
}

function christmas_parse_date(string $value): ?DateTimeImmutable
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone(CHRISTMAS_TIMEZONE));
    return ($date && $date->format('Y-m-d') === $value) ? $date : null;
}

/**
 * Valida una foto subida y la vuelve a codificar con GD: así se descartan
 * archivos disfrazados de imagen y se eliminan los metadatos (p. ej. GPS).
 * Devuelve ['tmp','mime','size','width','height','original_name'] o una clave de error.
 */
function christmas_prepare_photo(array $file): array|string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_INI_SIZE || ($file['error'] ?? 0) === UPLOAD_ERR_FORM_SIZE) {
        return 'photos_size';
    }
    if (($file['error'] ?? -1) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$file['tmp_name'])) {
        return 'photos_failed';
    }
    $size = (int)$file['size'];
    if ($size <= 0 || $size > CHRISTMAS_MAX_PHOTO_BYTES) {
        return 'photos_size';
    }
    $path = (string)$file['tmp_name'];
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string)finfo_file($finfo, $path) : '';
    }
    $info = @getimagesize($path);
    if ($info === false || !isset(CHRISTMAS_PHOTO_TYPES[$info['mime']]) || ($mime !== '' && $mime !== $info['mime'])) {
        return 'photos_type';
    }
    $mime = $info['mime'];
    [$width, $height] = $info;
    if ($width < 1 || $height < 1 || $width * $height > CHRISTMAS_MAX_PHOTO_PIXELS) {
        return 'photos_type';
    }

    $originalName = christmas_clean(basename(str_replace('\\', '/', (string)$file['name'])));
    $originalName = function_exists('mb_substr') ? mb_substr($originalName, 0, 150, 'UTF-8') : substr($originalName, 0, 150);
    $result = ['tmp' => $path, 'mime' => $mime, 'size' => $size, 'width' => $width, 'height' => $height, 'original_name' => $originalName ?: 'photo', 'cleanup' => false];

    if (!function_exists('imagecreatefromstring')) {
        return $result; // Sin GD: se guarda el archivo ya validado, con nombre aleatorio y fuera de la web.
    }
    $image = @imagecreatefromstring((string)file_get_contents($path));
    if ($image === false) {
        return 'photos_type';
    }
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        $rotation = [3 => 180, 6 => -90, 8 => 90][(int)($exif['Orientation'] ?? 1)] ?? 0;
        if ($rotation !== 0 && ($rotated = imagerotate($image, $rotation, 0)) !== false) {
            $image = $rotated;
        }
    }
    $width = imagesx($image);
    $height = imagesy($image);
    $maxSide = 2400;
    if (max($width, $height) > $maxSide) {
        $scale = $maxSide / max($width, $height);
        $resized = imagescale($image, max(1, (int)round($width * $scale)), max(1, (int)round($height * $scale)), IMG_BICUBIC);
        if ($resized !== false) {
            $image = $resized;
            $width = imagesx($image);
            $height = imagesy($image);
        }
    }
    $target = tempnam(sys_get_temp_dir(), 'xmas');
    if ($target === false) {
        return 'photos_failed';
    }
    $ok = match ($mime) {
        'image/png' => imagepng($image, $target, 7),
        'image/webp' => function_exists('imagewebp') ? imagewebp($image, $target, 84) : false,
        default => imagejpeg($image, $target, 84),
    };
    if (!$ok) {
        @unlink($target);
        return $result;
    }
    clearstatcache(true, $target);
    return ['tmp' => $target, 'mime' => $mime, 'size' => (int)filesize($target), 'width' => $width, 'height' => $height, 'original_name' => $result['original_name'], 'cleanup' => true];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_base_path() . CHRISTMAS_PAGE_PATH, true, 303);
    exit;
}

// Si el envío supera post_max_size, PHP descarta todo el formulario.
if ($_POST === [] && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    christmas_fail(413, ['too_large'], $language);
}

if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
    christmas_fail(400, ['session'], $language);
}
if (!christmas_campaign_enabled()) {
    christmas_fail(410, ['closed'], $language);
}
// Campo trampa: se responde como recibido para no enseñar a los bots.
if (trim((string)($_POST['contact_note_internal'] ?? '')) !== '') {
    header('Location: ' . app_base_path() . CHRISTMAS_PAGE_PATH . 'thanks.php', true, 303);
    exit;
}
$now = time();
if (!empty($_SESSION['christmas_submitted_at']) && ($now - (int)$_SESSION['christmas_submitted_at']) < 20) {
    christmas_fail(429, ['wait'], $language);
}

$errors = [];
$fullName = christmas_clean((string)($_POST['full_name'] ?? ''));
$email = strtolower(christmas_clean((string)($_POST['email'] ?? '')));
$phone = christmas_clean((string)($_POST['phone'] ?? ''));
$address = christmas_clean((string)($_POST['property_address'] ?? ''));
$city = christmas_clean((string)($_POST['city'] ?? ''));
$zip = christmas_clean((string)($_POST['zip_code'] ?? ''));
$details = christmas_clean((string)($_POST['additional_details'] ?? ''), true);
$contactMethod = christmas_post_choice('contact_method', CHRISTMAS_CONTACT_METHODS);
$propertyType = christmas_post_choice('property_type', CHRISTMAS_PROPERTY_TYPES);
$package = christmas_post_choice('package_type', CHRISTMAS_PACKAGES);
$supplier = christmas_post_choice('decoration_supplier', CHRISTMAS_SUPPLIERS);
$size = christmas_post_choice('property_size', CHRISTMAS_PROPERTY_SIZES);
$consent = (string)($_POST['privacy_consent'] ?? '') === '1';

$areas = [];
foreach ((array)($_POST['decoration_areas'] ?? []) as $area) {
    if (is_string($area) && isset(CHRISTMAS_DECORATION_AREAS[$area])) {
        $areas[] = $area;
    }
}
$areas = array_values(array_unique($areas));

$phoneDigits = preg_replace('/\D/', '', $phone);
if ($fullName === '' || christmas_length($fullName) < 2 || christmas_length($fullName) > 120) $errors[] = 'full_name';
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email';
if (christmas_length($phone) > 40 || !preg_match('/^\+?[0-9\s().-]+$/', $phone)
    || !(strlen($phoneDigits) === 10 || (strlen($phoneDigits) === 11 && $phoneDigits[0] === '1') || (str_starts_with($phone, '+') && strlen($phoneDigits) >= 8 && strlen($phoneDigits) <= 15))) {
    $errors[] = 'phone';
}
if ($contactMethod === null) $errors[] = 'contact_method';
if (christmas_length($address) < 5 || christmas_length($address) > 200) $errors[] = 'property_address';
if ($city === '' || christmas_length($city) > 100) $errors[] = 'city';
if (!preg_match('/^\d{5}(-\d{4})?$/', $zip)) $errors[] = 'zip_code';
if ($propertyType === null) $errors[] = 'property_type';
if ($package === null) $errors[] = 'package_type';
if ($areas === []) $errors[] = 'decoration_areas';
if ($supplier === null) $errors[] = 'decoration_supplier';
if ($size === null) $errors[] = 'property_size';
if (christmas_length($details) > 3000) $errors[] = 'additional_details';
if (!$consent) $errors[] = 'privacy_consent';

$today = christmas_now()->setTime(0, 0);
$latest = $today->modify('+400 days');
$installRaw = christmas_clean((string)($_POST['installation_date'] ?? ''));
$removalRaw = christmas_clean((string)($_POST['removal_date'] ?? ''));
$installDate = $installRaw === '' ? null : christmas_parse_date($installRaw);
$removalDate = $removalRaw === '' ? null : christmas_parse_date($removalRaw);
if ($installRaw !== '' && ($installDate === null || $installDate < $today || $installDate > $latest)) $errors[] = 'installation_date';
if ($removalRaw !== '' && ($removalDate === null || $removalDate < $today || $removalDate > $latest
    || ($installDate !== null && $removalDate <= $installDate))) {
    $errors[] = 'removal_date';
}

// Fotos (opcionales).
$photos = [];
$uploads = $_FILES['photos'] ?? null;
if (is_array($uploads) && is_array($uploads['name'] ?? null)) {
    $files = [];
    foreach ($uploads['name'] as $index => $name) {
        if (($uploads['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $files[] = [
            'name' => $name,
            'tmp_name' => $uploads['tmp_name'][$index] ?? '',
            'error' => $uploads['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $uploads['size'][$index] ?? 0,
        ];
    }
    if (count($files) > CHRISTMAS_MAX_PHOTOS) {
        $errors[] = 'photos_count';
    } elseif ($errors === []) {
        foreach ($files as $file) {
            $prepared = christmas_prepare_photo($file);
            if (is_string($prepared)) {
                $errors[] = $prepared;
                break;
            }
            $photos[] = $prepared;
        }
    }
}

$cleanupTemp = static function () use (&$photos): void {
    foreach ($photos as $photo) {
        if ($photo['cleanup']) {
            @unlink($photo['tmp']);
        }
    }
};

if ($errors !== []) {
    $cleanupTemp();
    christmas_fail(422, $errors, $language);
}

$storedFiles = [];
try {
    $pdo = database();

    $recent = $pdo->prepare('SELECT COUNT(*) FROM christmas_quote_requests WHERE email = :email AND created_at > (UTC_TIMESTAMP() - INTERVAL 1 DAY)');
    $recent->execute([':email' => $email]);
    if ((int)$recent->fetchColumn() >= 3) {
        $cleanupTemp();
        christmas_fail(429, ['limit'], $language);
    }

    $campaign = christmas_campaign_values();
    $uploadDir = christmas_upload_dir();
    if ($photos !== [] && !is_dir($uploadDir) && !@mkdir($uploadDir, 0750, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Upload directory is missing and could not be created: ' . $uploadDir);
    }

    $pdo->beginTransaction();
    $insert = $pdo->prepare('INSERT INTO christmas_quote_requests
        (request_number, full_name, email, phone, contact_method, property_address, city, zip_code, property_type,
         package_type, decoration_areas, decoration_supplier, property_size, installation_date, removal_date,
         additional_details, uploaded_photos, privacy_consent, consent_at, language, campaign_source, discount_percentage, request_status)
        VALUES
        (:request_number, :full_name, :email, :phone, :contact_method, :property_address, :city, :zip_code, :property_type,
         :package_type, :decoration_areas, :decoration_supplier, :property_size, :installation_date, :removal_date,
         :additional_details, :uploaded_photos, 1, UTC_TIMESTAMP(), :language, :campaign_source, :discount_percentage, \'NEW_REQUEST\')');
    $values = [
        ':full_name' => $fullName,
        ':email' => $email,
        ':phone' => $phone,
        ':contact_method' => $contactMethod,
        ':property_address' => $address,
        ':city' => $city,
        ':zip_code' => $zip,
        ':property_type' => $propertyType,
        ':package_type' => $package,
        ':decoration_areas' => implode(',', $areas),
        ':decoration_supplier' => $supplier,
        ':property_size' => $size,
        ':installation_date' => $installDate?->format('Y-m-d'),
        ':removal_date' => $removalDate?->format('Y-m-d'),
        ':additional_details' => $details !== '' ? $details : null,
        ':uploaded_photos' => count($photos),
        ':language' => $language,
        ':campaign_source' => $campaign['campaign_source'],
        ':discount_percentage' => $campaign['discount_percentage'],
    ];
    $requestNumber = '';
    for ($attempt = 0; $attempt < 3; $attempt++) {
        $requestNumber = 'XMAS-' . strtoupper(bin2hex(random_bytes(4)));
        try {
            $insert->execute($values + [':request_number' => $requestNumber]);
            break;
        } catch (PDOException $duplicate) {
            if ($attempt === 2 || ($duplicate->errorInfo[1] ?? 0) !== 1062) {
                throw $duplicate;
            }
        }
    }
    $requestId = (int)$pdo->lastInsertId();

    $savedPhotos = [];
    $insertPhoto = $pdo->prepare('INSERT INTO christmas_quote_photos (request_id, stored_name, original_name, mime_type, size_bytes, width, height)
        VALUES (:request_id, :stored_name, :original_name, :mime_type, :size_bytes, :width, :height)');
    foreach ($photos as $photo) {
        $storedName = bin2hex(random_bytes(16)) . '.' . CHRISTMAS_PHOTO_TYPES[$photo['mime']];
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedName;
        $moved = $photo['cleanup'] ? @rename($photo['tmp'], $destination) || @copy($photo['tmp'], $destination) : @move_uploaded_file($photo['tmp'], $destination);
        if (!$moved) {
            throw new RuntimeException('Photo could not be stored in ' . $uploadDir);
        }
        $storedFiles[] = $destination;
        @chmod($destination, 0640);
        $insertPhoto->execute([
            ':request_id' => $requestId,
            ':stored_name' => $storedName,
            ':original_name' => $photo['original_name'],
            ':mime_type' => $photo['mime'],
            ':size_bytes' => $photo['size'],
            ':width' => min(65535, $photo['width']),
            ':height' => min(65535, $photo['height']),
        ]);
        $savedPhotos[] = ['original_name' => $photo['original_name'], 'mime_type' => $photo['mime'], 'size_bytes' => $photo['size'], 'path' => $destination];
    }
    $pdo->commit();
    $cleanupTemp();

    // La solicitud ya quedó guardada; un fallo del correo no la invalida (queda en el log de PHP).
    $request = [
        'id' => $requestId,
        'request_number' => $requestNumber,
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'contact_method' => $contactMethod,
        'property_address' => $address,
        'city' => $city,
        'zip_code' => $zip,
        'property_type' => $propertyType,
        'package_type' => $package,
        'decoration_areas_list' => $areas,
        'decoration_supplier' => $supplier,
        'property_size' => $size,
        'installation_date' => $installDate?->format('Y-m-d'),
        'removal_date' => $removalDate?->format('Y-m-d'),
        'additional_details' => $details,
        'language' => $language,
        'campaign_source' => $campaign['campaign_source'],
        'discount_percentage' => $campaign['discount_percentage'],
    ];
    christmas_send_admin_notification($request, $savedPhotos);
    christmas_send_customer_confirmation($request);

    $_SESSION['christmas_submitted_at'] = $now;
    $_SESSION['christmas_last_reference'] = $requestNumber;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: ' . app_base_path() . CHRISTMAS_PAGE_PATH . 'thanks.php?ref=' . rawurlencode($requestNumber), true, 303);
    exit;
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    foreach ($storedFiles as $file) {
        @unlink($file);
    }
    $cleanupTemp();
    error_log('Chelart Christmas request could not be saved: ' . $error->getMessage());
    christmas_fail(503, ['server'], $language);
}
