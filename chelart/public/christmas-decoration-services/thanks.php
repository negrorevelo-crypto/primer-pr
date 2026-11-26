<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/christmas.php';

$reference = (string)($_GET['ref'] ?? '');
if (!preg_match('/^XMAS-[A-F0-9]{8}$/', $reference)) {
    $reference = '';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <base href="<?= h(app_base_path()) ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png"><link rel="apple-touch-icon" sizes="180x180" href="favicon-180.png"><link rel="icon" type="image/png" sizes="512x512" href="favicon-512.png">
  <title>Christmas request received | Chelart</title>
  <script src="assets/js/language-init.js?v=20261125"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/site.css?v=20261126">
  <link rel="stylesheet" href="assets/css/christmas.css?v=20261127">
  <script defer src="assets/js/christmas-i18n.js?v=20261126"></script>
  <script defer src="assets/js/i18n.js?v=20261126"></script>
  <script defer src="assets/js/site.js?v=20261126"></script>
</head>
<body class="thanks-page">
  <header class="site-header quote-header"><div class="nav-wrap"><a class="brand" href="./" aria-label="Chelart home"><img src="assets/img/chelart-logo-192.png" alt="Chelart Construction logo" width="192" height="192" sizes="96px"></a><div class="quote-nav-actions"><div class="language-switch" role="group" aria-label="Select language"><button type="button" data-language-switch="en">EN</button><span aria-hidden="true">/</span><button type="button" data-language-switch="es">ES</button></div></div></div></header>
  <main class="thanks-card">
    <span class="thanks-mark" aria-hidden="true">✓</span>
    <p class="eyebrow">Christmas decoration request</p>
    <h1>Thank you for choosing CHELART!</h1>
    <p>Your Christmas decoration request has been received. Our team will review your information and contact you shortly with a personalized quote.</p>
    <?php if ($reference !== ''): ?><p class="reference-code">Request number <strong><?= h($reference) ?></strong></p><?php endif; ?>
    <p class="xmas-thanks-note">We also sent a confirmation to your email.</p>
    <a class="button button-primary" href="./">Return to Chelart <span aria-hidden="true">↗</span></a>
  </main>
</body>
</html>
