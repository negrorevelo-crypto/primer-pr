<?php
declare(strict_types=1);

/**
 * Correos de la campaña de Navidad. Usan el mismo transporte del hosting que el
 * formulario general (mail() de PHP configurado en Plesk) y el mismo remitente
 * QUOTE_MAIL_FROM. No se cambian credenciales ni la configuración de correo.
 */

function christmas_mail_header_text(string $text): string
{
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

function christmas_mail_sender(): ?string
{
    $sender = chelart_setting('QUOTE_MAIL_FROM', 'website@chelartconstruction.revelco.com.co');
    if (!filter_var($sender, FILTER_VALIDATE_EMAIL)) {
        error_log('Chelart Christmas mail skipped: QUOTE_MAIL_FROM is invalid.');
        return null;
    }
    return $sender;
}

/** Envía un correo de texto, con adjuntos opcionales [['name','mime','path']]. */
function christmas_send_mail(string $to, string $subject, string $body, string $replyTo, array $attachments = []): bool
{
    $sender = christmas_mail_sender();
    if ($sender === null || !filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $headers = [
        'From: ' . christmas_mail_header_text('CHELART') . ' <' . $sender . '>',
        'Reply-To: ' . $replyTo,
        'MIME-Version: 1.0',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $body = str_replace("\n", "\r\n", $body);

    if ($attachments === []) {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $message = chunk_split(base64_encode($body));
    } else {
        $boundary = 'chelart-' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
        $message = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($body));
        foreach ($attachments as $attachment) {
            $content = @file_get_contents($attachment['path']);
            if ($content === false) {
                continue;
            }
            $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $attachment['name']);
            $message .= "--{$boundary}\r\nContent-Type: {$attachment['mime']}; name=\"{$name}\"\r\n"
                . "Content-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"{$name}\"\r\n\r\n"
                . chunk_split(base64_encode($content));
        }
        $message .= "--{$boundary}--\r\n";
    }

    try {
        $sent = mail($to, christmas_mail_header_text($subject), $message, implode("\r\n", $headers));
    } catch (Throwable $error) {
        error_log('Chelart Christmas mail failed: ' . $error->getMessage());
        return false;
    }
    if (!$sent) {
        error_log('Chelart Christmas mail could not be queued for ' . $to . '.');
    }
    return $sent;
}

function christmas_format_date(?string $date, string $language): string
{
    if ($date === null || $date === '') {
        return $language === 'es' ? 'Por definir' : 'To be confirmed';
    }
    $value = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$value) {
        return $date;
    }
    if ($language === 'es') {
        $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return $value->format('j') . ' de ' . $months[(int)$value->format('n') - 1] . ' de ' . $value->format('Y');
    }
    return $value->format('F j, Y');
}

function christmas_area_labels(array $areas, string $language): string
{
    return implode(', ', array_map(static fn(string $area): string => christmas_label(CHRISTMAS_DECORATION_AREAS, $area, $language), $areas));
}

/** Aviso al equipo de Chelart con todos los datos, fotos adjuntas y enlace privado. */
function christmas_send_admin_notification(array $request, array $photos): bool
{
    $adminUrl = quote_admin_url() . 'christmas.php?id=' . (int)$request['id'];
    $promo = (float)$request['discount_percentage'] > 0
        ? rtrim(rtrim(number_format((float)$request['discount_percentage'], 2), '0'), '.') . '% OFF (' . $request['campaign_source'] . ')'
        : 'Not eligible (' . $request['campaign_source'] . ')';

    $photoLines = [];
    $attachments = [];
    $attachedBytes = 0;
    foreach ($photos as $index => $photo) {
        $photoLines[] = '  ' . ($index + 1) . '. ' . $photo['original_name'] . ' (' . round($photo['size_bytes'] / 1024) . ' KB)';
        if ($attachedBytes + $photo['size_bytes'] <= 15 * 1024 * 1024) {
            $attachedBytes += $photo['size_bytes'];
            $attachments[] = [
                'name' => sprintf('%s-photo-%d.%s', $request['request_number'], $index + 1, CHRISTMAS_PHOTO_TYPES[$photo['mime_type']] ?? 'jpg'),
                'mime' => $photo['mime_type'],
                'path' => $photo['path'],
            ];
        }
    }

    $lines = [
        'A new Christmas decoration request was submitted on chelart.com.',
        '',
        'Request number: ' . $request['request_number'],
        'Campaign: ' . $request['campaign_source'],
        'Promotion: ' . $promo,
        'Status: NEW REQUEST',
        '',
        '— CUSTOMER —',
        'Name: ' . $request['full_name'],
        'Email: ' . $request['email'],
        'Phone: ' . $request['phone'],
        'Preferred contact: ' . christmas_label(CHRISTMAS_CONTACT_METHODS, $request['contact_method']),
        'Language: ' . ($request['language'] === 'es' ? 'Spanish' : 'English'),
        '',
        '— PROPERTY —',
        'Address: ' . $request['property_address'],
        'City / ZIP: ' . $request['city'] . ' / ' . $request['zip_code'],
        'Property type: ' . christmas_label(CHRISTMAS_PROPERTY_TYPES, $request['property_type']),
        'Approximate size: ' . christmas_label(CHRISTMAS_PROPERTY_SIZES, $request['property_size']),
        '',
        '— SERVICE —',
        'Package: ' . christmas_label(CHRISTMAS_PACKAGES, $request['package_type']),
        'Decoration areas: ' . christmas_area_labels($request['decoration_areas_list'], 'en'),
        'Decorations supplied by: ' . christmas_label(CHRISTMAS_SUPPLIERS, $request['decoration_supplier']),
        'Preferred installation date: ' . christmas_format_date($request['installation_date'], 'en'),
        'Preferred removal date: ' . christmas_format_date($request['removal_date'], 'en'),
        '',
        'Additional details:',
        ($request['additional_details'] ?? '') !== '' ? $request['additional_details'] : '—',
        '',
        '— PHOTOS (' . count($photos) . ') —',
        $photos === [] ? 'No photos attached.' : implode("\n", $photoLines),
        count($attachments) < count($photos) ? 'Some photos were too large to attach; view them in the admin panel.' : '',
        '',
        'Manage this lead (private, sign-in required): ' . $adminUrl,
        '',
        'Reminder: prices are quoted individually after the team reviews the request. No automatic charges are made.',
    ];

    return christmas_send_mail(
        christmas_notify_email(),
        'NEW CHRISTMAS DECORATION REQUEST – CHELART',
        implode("\n", $lines),
        $request['email'],
        $attachments
    );
}

/** Confirmación al cliente, en el idioma que tenía seleccionado al enviar. */
function christmas_send_customer_confirmation(array $request): bool
{
    $es = $request['language'] === 'es';
    $lang = $es ? 'es' : 'en';
    $promo = (float)$request['discount_percentage'] > 0;
    $percent = rtrim(rtrim(number_format((float)$request['discount_percentage'], 2), '0'), '.');
    $firstName = trim(explode(' ', trim($request['full_name']))[0] ?? '');

    $lines = $es ? [
        'Hola ' . $firstName . ':',
        '',
        '¡Gracias por elegir CHELART! Recibimos tu solicitud de decoración navideña.',
        'Nuestro equipo revisará la información y te contactará pronto con una cotización personalizada.',
        '',
        'Número de solicitud: ' . $request['request_number'],
        'Paquete: ' . christmas_label(CHRISTMAS_PACKAGES, $request['package_type'], 'es'),
        'Áreas: ' . christmas_area_labels($request['decoration_areas_list'], 'es'),
        'Dirección: ' . $request['property_address'] . ', ' . $request['city'] . ' ' . $request['zip_code'],
        'Instalación preferida: ' . christmas_format_date($request['installation_date'], 'es'),
        'Retiro preferido: ' . christmas_format_date($request['removal_date'], 'es'),
        'Te contactaremos por: ' . christmas_label(CHRISTMAS_CONTACT_METHODS, $request['contact_method'], 'es'),
        '',
        $promo ? 'Tu solicitud se recibió dentro de la promoción Black Friday: ' . $percent . '% de descuento en el paquete de instalación y retiro, que se aplicará en la cotización que prepare nuestro equipo.' : '',
        'Este correo no es una cotización ni un precio final. El valor se define después de que el equipo de Chelart revise tu propiedad y tus preferencias. No se realiza ningún cobro.',
        '',
        '¿Quieres agregar algo? Responde a este correo o llámanos al (689) 250-9674.',
        '',
        'CHELART Construction',
        'DeBary · Orlando · Central Florida',
        'https://chelart.com',
    ] : [
        'Hi ' . $firstName . ',',
        '',
        'Thank you for choosing CHELART! Your Christmas decoration request has been received.',
        'Our team will review your information and contact you shortly with a personalized quote.',
        '',
        'Request number: ' . $request['request_number'],
        'Package: ' . christmas_label(CHRISTMAS_PACKAGES, $request['package_type']),
        'Areas: ' . christmas_area_labels($request['decoration_areas_list'], 'en'),
        'Address: ' . $request['property_address'] . ', ' . $request['city'] . ' ' . $request['zip_code'],
        'Preferred installation: ' . christmas_format_date($request['installation_date'], 'en'),
        'Preferred removal: ' . christmas_format_date($request['removal_date'], 'en'),
        'We will contact you by: ' . christmas_label(CHRISTMAS_CONTACT_METHODS, $request['contact_method']),
        '',
        $promo ? 'Your request was received during our Black Friday promotion: ' . $percent . '% off the installation and removal package, applied to the quote our team prepares for you.' : '',
        'This email is not a quote or a final price. Pricing is set after the Chelart team reviews your property and preferences. No charges are made.',
        '',
        'Want to add something? Reply to this email or call us at (689) 250-9674.',
        '',
        'CHELART Construction',
        'DeBary · Orlando · Central Florida',
        'https://chelart.com',
    ];

    $replyTo = chelart_setting('QUOTE_NOTIFY_EMAIL', 'chelartusa@gmail.com');
    return christmas_send_mail(
        $request['email'],
        $es ? 'Tu solicitud de decoración navideña – CHELART' : 'Your Christmas Decoration Request – CHELART',
        implode("\n", array_filter($lines, static fn($line) => $line !== null)),
        filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : christmas_notify_email()
    );
}
