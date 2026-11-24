<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
$reference = (string)($_GET['ref'] ?? '');
if (!preg_match('/^CH-[A-Z0-9-]{8,24}$/', $reference)) {
    $reference = '';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?= h(app_base_path()) ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png"><link rel="apple-touch-icon" sizes="180x180" href="favicon-180.png"><link rel="icon" type="image/png" sizes="512x512" href="favicon-512.png">
  <title>Inquiry received | Chelart</title>
  <script src="assets/js/language-init.js?v=20261124"></script>
  <link rel="stylesheet" href="assets/css/site.css?v=20261124">
  <script defer src="assets/js/i18n.js?v=20261124"></script>
  <script defer src="assets/js/site.js?v=20261124"></script>
</head>
<body class="thanks-page">
  <header class="site-header quote-header"><div class="nav-wrap"><a class="brand" href="./" aria-label="Chelart home"><img src="assets/img/chelart-logo-192.png" alt="Chelart Construction logo" width="192" height="192" srcset="assets/img/chelart-logo-192.png 192w" sizes="96px"></a><div class="quote-nav-actions"><div class="language-switch" role="group" aria-label="Select language"><button type="button" data-language-switch="en">EN</button><span aria-hidden="true">/</span><button type="button" data-language-switch="es">ES</button></div></div></div></header>
  <main class="thanks-card">
    <span class="thanks-mark" aria-hidden="true">✓</span>
    <p class="eyebrow">Thank you for reaching out</p>
    <h1>Your inquiry is with Chelart.</h1>
    <p>We’ll review the project details and follow up using your preferred contact method.</p>
    <?php if ($reference !== ''): ?><p class="reference-code">Reference <strong><?= h($reference) ?></strong></p><?php endif; ?>
    <a class="button button-primary" href="./">Return to Chelart <span aria-hidden="true">↗</span></a>
  </main>
</body>
</html>
