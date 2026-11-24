<?php
declare(strict_types=1);

/** Send a plain-text alert after a quote is safely stored in MariaDB. */
function send_quote_notification(string $reference, array $quote, array $services): bool
{
    $recipient = chelart_setting('QUOTE_NOTIFY_EMAIL', 'chelartusa@gmail.com');
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        error_log('Chelart quote notification skipped: QUOTE_NOTIFY_EMAIL is invalid.');
        return false;
    }

    $sender = chelart_setting('QUOTE_MAIL_FROM', 'website@chelartconstruction.revelco.com.co');
    if (!filter_var($sender, FILTER_VALIDATE_EMAIL)) {
        error_log('Chelart quote notification skipped: QUOTE_MAIL_FROM is invalid.');
        return false;
    }

    $serviceNames = array_map(
        static fn(string $key): string => SERVICE_LABELS[$key] ?? $key,
        $services
    );
    $preferred = ['email' => 'Email', 'phone' => 'Phone', 'text' => 'Text message'];
    $property = ['single-family' => 'Single-family home', 'condo' => 'Condo', 'commercial' => 'Commercial', 'other' => 'Other'];
    $timeline = ['asap' => 'As soon as possible', 'within-month' => 'Within a month', 'one-to-three-months' => '1–3 months', 'planning' => 'Planning ahead'];
    $body = implode("\r\n", [
        'A new quote request was submitted on the Chelart website.',
        '',
        'Reference: ' . $reference,
        'Name: ' . $quote['full_name'],
        'Email: ' . $quote['email'],
        'Phone: ' . $quote['phone'],
        'City / ZIP: ' . $quote['city'] . ' / ' . $quote['postal_code'],
        'Property: ' . ($property[$quote['property_type']] ?? $quote['property_type']),
        'Timeline: ' . ($timeline[$quote['project_timeline']] ?? $quote['project_timeline']),
        'Preferred contact: ' . ($preferred[$quote['preferred_contact']] ?? $quote['preferred_contact']),
        'Services: ' . implode(', ', $serviceNames),
        '',
        'Project details:',
        $quote['scope_description'],
        '',
        'Private request history: ' . quote_admin_url(),
        'Sign in with your Chelart admin account to view the request history.',
    ]);

    $subject = 'Chelart quote request ' . $reference;
    $headers = [
        'From: Chelart Website <' . $sender . '>',
        'Reply-To: ' . $quote['email'],
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    try {
        $sent = mail($recipient, $subject, $body, implode("\r\n", $headers));
    } catch (Throwable $error) {
        error_log('Chelart quote notification failed for ' . $reference . ': ' . $error->getMessage());
        return false;
    }

    if (!$sent) {
        error_log('Chelart quote notification could not be queued for ' . $reference . '.');
    }
    return $sent;
}

function quote_admin_url(): string
{
    $configured = trim(chelart_setting('QUOTE_ADMIN_URL'));
    $url = $configured !== '' ? $configured : 'https://chelartconstruction.revelco.com.co/admin/';
    if (!filter_var($url, FILTER_VALIDATE_URL) || strtolower((string)parse_url($url, PHP_URL_SCHEME)) !== 'https') {
        error_log('Chelart quote notification skipped: QUOTE_ADMIN_URL must be a valid HTTPS URL.');
        return 'https://chelartconstruction.revelco.com.co/admin/';
    }

    return rtrim($url, '/') . '/';
}
