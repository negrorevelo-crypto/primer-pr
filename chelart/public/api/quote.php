<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/notifications.php';

const SERVICE_LABELS = [
    'repairs' => 'Repairs & maintenance',
    'remodeling' => 'Kitchen & bath remodeling',
    'design' => 'Interior design & space planning',
    'installations' => 'Lighting & installations',
    'other' => 'Something else',
];

function field_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function fail(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="' . h(app_base_path()) . '"><title>Quote request | Chelart</title><script src="assets/js/language-init.js?v=20261124"></script><link rel="stylesheet" href="assets/css/site.css?v=20261124"><script defer src="assets/js/i18n.js?v=20261124"></script></head><body class="thanks-page"><main class="thanks-card"><p class="eyebrow">Request not sent</p><h1>We couldn’t submit that yet.</h1><p>' . h($message) . '</p><a class="button button-primary" href="quote.php">Return to the quote form</a></main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /quote.php', true, 303);
    exit;
}

$token = (string)($_POST['csrf_token'] ?? '');
if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
    fail(400, 'Please return to the quote form and try again.');
}

// A filled honeypot field is silently treated as received to avoid teaching bots about the trap.
if (trim((string)($_POST['contact_note_internal'] ?? '')) !== '') {
    header('Location: /quote-thanks.php', true, 303);
    exit;
}

$now = time();
if (!empty($_SESSION['quote_submitted_at']) && ($now - (int)$_SESSION['quote_submitted_at']) < 20) {
    fail(429, 'Please wait a moment before sending another request.');
}

$fullName = trim((string)($_POST['full_name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$city = trim((string)($_POST['city'] ?? ''));
$postalCode = trim((string)($_POST['postal_code'] ?? ''));
$propertyType = trim((string)($_POST['property_type'] ?? ''));
$timeline = trim((string)($_POST['project_timeline'] ?? ''));
$scope = trim((string)($_POST['scope_description'] ?? ''));
$preferredContact = trim((string)($_POST['preferred_contact'] ?? ''));
$consent = (string)($_POST['consent'] ?? '') === '1';
$rawServices = $_POST['services'] ?? [];
$services = [];
if (is_array($rawServices)) {
    foreach ($rawServices as $service) {
        if (is_string($service) && isset(SERVICE_LABELS[$service])) {
            $services[] = $service;
        }
    }
    $services = array_values(array_unique($services));
}

$propertyTypes = ['single-family', 'condo', 'commercial', 'other'];
$timelines = ['asap', 'within-month', 'one-to-three-months', 'planning'];
$contactMethods = ['email', 'phone', 'text'];
$fieldLimits = [
    'name' => field_length($fullName),
    'email' => strlen($email),
    'phone' => field_length($phone),
    'city' => field_length($city),
    'postal' => field_length($postalCode),
    'scope' => field_length($scope),
];

if ($fullName === '' || $fieldLimits['name'] > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || $fieldLimits['email'] > 254 || $phone === '' || $fieldLimits['phone'] > 40
    || $city === '' || $fieldLimits['city'] > 100 || !preg_match('/^\d{5}$/', $postalCode)
    || !in_array($propertyType, $propertyTypes, true) || !in_array($timeline, $timelines, true)
    || !in_array($preferredContact, $contactMethods, true) || $scope === '' || $fieldLimits['scope'] > 3000
    || count($services) === 0 || !$consent) {
    fail(422, 'Some details are missing or need attention. Please review the form and try again.');
}

try {
    $pdo = database();
    $pdo->beginTransaction();
    $reference = 'CH-' . strtoupper(bin2hex(random_bytes(4)));
    $insert = $pdo->prepare('INSERT INTO quote_requests (request_code, full_name, email, phone, city, postal_code, property_type, project_timeline, scope_description, preferred_contact, consent_at) VALUES (:request_code, :full_name, :email, :phone, :city, :postal_code, :property_type, :project_timeline, :scope_description, :preferred_contact, UTC_TIMESTAMP())');
    $insert->execute([
        ':request_code' => $reference,
        ':full_name' => $fullName,
        ':email' => $email,
        ':phone' => $phone,
        ':city' => $city,
        ':postal_code' => $postalCode,
        ':property_type' => $propertyType,
        ':project_timeline' => $timeline,
        ':scope_description' => $scope,
        ':preferred_contact' => $preferredContact,
    ]);
    $requestId = (int)$pdo->lastInsertId();
    $insertService = $pdo->prepare('INSERT INTO quote_request_services (quote_request_id, service_key, service_label) VALUES (:request_id, :service_key, :service_label)');
    foreach ($services as $serviceKey) {
        $insertService->execute([
            ':request_id' => $requestId,
            ':service_key' => $serviceKey,
            ':service_label' => SERVICE_LABELS[$serviceKey],
        ]);
    }
    $pdo->commit();
    // Keep the saved request authoritative even if Plesk cannot queue outgoing mail.
    send_quote_notification($reference, [
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'city' => $city,
        'postal_code' => $postalCode,
        'property_type' => $propertyType,
        'project_timeline' => $timeline,
        'scope_description' => $scope,
        'preferred_contact' => $preferredContact,
    ], $services);
    $_SESSION['quote_submitted_at'] = $now;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: /quote-thanks.php?ref=' . rawurlencode($reference), true, 303);
    exit;
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Chelart quote request could not be saved: ' . $error->getMessage());
    fail(503, 'Please call (689) 250-9674 or email chelartusa@gmail.com and we’ll help you directly.');
}

