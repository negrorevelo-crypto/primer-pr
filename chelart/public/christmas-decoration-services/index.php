<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_once dirname(__DIR__, 2) . '/config/christmas.php';

// Con la campaña apagada la página no se muestra (302: se puede volver a activar sin perder posicionamiento).
if (!christmas_campaign_enabled()) {
    header('Location: ' . app_base_path(), true, 302);
    exit;
}

$promoActive = christmas_promo_active();
$discount = christmas_discount_percent();
$selectedPackage = (string)($_GET['package'] ?? '');
if (!isset(CHRISTMAS_PACKAGES[$selectedPackage])) {
    $selectedPackage = '';
}
$today = christmas_now()->format('Y-m-d');
$maxDate = christmas_now()->modify('+400 days')->format('Y-m-d');
$page = CHRISTMAS_PAGE_PATH;
$canonical = christmas_public_url(CHRISTMAS_PAGE_PATH);

// Foto principal: si existe la foto definitiva de temporada se usa; si no, la foto actual de la portada.
$heroBase = 'assets/img/christmas/christmas-home-hero';
$publicRoot = dirname(__DIR__);
$heroIsSeasonal = is_file($publicRoot . '/' . $heroBase . '.jpg');
if (!$heroIsSeasonal) {
    $heroBase = 'assets/img/projects/hero-florida-home';
}
$heroSources = [];
foreach ([480, 800, 1200, 1600] as $width) {
    if (is_file($publicRoot . "/{$heroBase}-{$width}.jpg")) {
        $heroSources[] = "{$heroBase}-{$width}.jpg {$width}w";
    }
}
$heroInfo = @getimagesize($publicRoot . "/{$heroBase}.jpg") ?: [1672, 941];
$ogImage = christmas_public_url(is_file($publicRoot . "/{$heroBase}-1200.jpg") ? "{$heroBase}-1200.jpg" : "{$heroBase}.jpg");

$seoTitle = 'Christmas Light Installation Orlando & DeBary, FL | Chelart';
$seoDescription = 'Professional Christmas light installation, holiday decoration, seasonal maintenance and removal for homes in DeBary, Orlando and Central Florida.'
    . ($promoActive ? " Black Friday: {$discount}% off before Dec 1." : ' Request a free quote.');

$structuredData = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Service',
            '@id' => $canonical . '#service',
            'name' => 'Christmas Decoration Service',
            'serviceType' => 'Christmas light installation and holiday decoration',
            'description' => 'Professional installation of Christmas lights and holiday decorations, seasonal maintenance and post-holiday removal for homes and businesses in Central Florida.',
            'url' => $canonical,
            'image' => $ogImage,
            'provider' => [
                '@type' => 'HomeAndConstructionBusiness',
                'name' => 'Chelart Construction LLC',
                'url' => 'https://chelart.com/',
                'telephone' => '+1-689-250-9674',
                'email' => 'chelartusa@gmail.com',
                'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'DeBary', 'addressRegion' => 'FL', 'postalCode' => '32713', 'addressCountry' => 'US'],
            ],
            'areaServed' => [
                ['@type' => 'City', 'name' => 'DeBary, FL'], ['@type' => 'City', 'name' => 'Deltona, FL'],
                ['@type' => 'City', 'name' => 'Sanford, FL'], ['@type' => 'City', 'name' => 'Lake Mary, FL'],
                ['@type' => 'City', 'name' => 'Orlando, FL'], ['@type' => 'AdministrativeArea', 'name' => 'Central Florida'],
            ],
            'availableLanguage' => ['en', 'es'],
            'hasOfferCatalog' => [
                '@type' => 'OfferCatalog',
                'name' => 'Christmas decoration packages',
                'itemListElement' => array_map(static fn(string $key): array => [
                    '@type' => 'Offer',
                    'itemOffered' => ['@type' => 'Service', 'name' => CHRISTMAS_PACKAGES[$key]['en'], 'description' => implode(' ', CHRISTMAS_PACKAGE_FEATURES[$key])],
                ], array_keys(CHRISTMAS_PACKAGE_FEATURES)),
            ],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Chelart', 'item' => 'https://chelart.com/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Christmas Decoration Services', 'item' => $canonical],
            ],
        ],
    ],
];

/** Imprime un grupo de opciones (radio o checkbox) con el estilo de "chip". */
function christmas_choice_chips(string $name, array $catalog, string $type, bool $required = false, string $checked = ''): void
{
    $first = true;
    foreach ($catalog as $key => $labels) {
        $inputName = $type === 'checkbox' ? $name . '[]' : $name;
        $attributes = ($required && $first && $type === 'radio') ? ' required' : '';
        $attributes .= $checked === $key ? ' checked' : '';
        echo '<label class="xmas-chip"><input type="' . $type . '" name="' . h($inputName) . '" value="' . h($key) . '"' . $attributes . '><span>' . h($labels['en']) . '</span></label>';
        $first = false;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?= h(app_base_path()) ?>">
  <meta name="theme-color" content="#f6f3ee">
  <title><?= h($seoTitle) ?></title>
  <meta name="description" content="<?= h($seoDescription) ?>">
  <link rel="canonical" href="<?= h($canonical) ?>">
  <meta property="og:type" content="website"><meta property="og:url" content="<?= h($canonical) ?>">
  <meta property="og:site_name" content="Chelart Construction">
  <meta property="og:title" content="<?= h($seoTitle) ?>">
  <meta property="og:description" content="<?= h($seoDescription) ?>">
  <meta property="og:image" content="<?= h($ogImage) ?>">
  <meta property="og:locale" content="en_US"><meta property="og:locale:alternate" content="es_US">
  <meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="<?= h($seoTitle) ?>">
  <meta name="twitter:description" content="<?= h($seoDescription) ?>"><meta name="twitter:image" content="<?= h($ogImage) ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png">
  <link rel="apple-touch-icon" sizes="180x180" href="favicon-180.png">
  <link rel="icon" type="image/png" sizes="512x512" href="favicon-512.png">
  <script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  <script src="assets/js/language-init.js?v=20261125"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
  <link rel="preload" as="image" href="<?= h($heroBase) ?>-800.jpg" imagesrcset="<?= h(implode(', ', $heroSources)) ?>" imagesizes="(max-width: 700px) 100vw, 55vw">
  <link rel="stylesheet" href="assets/css/site.css?v=20261126">
  <link rel="stylesheet" href="assets/css/christmas.css?v=20261130">
  <script defer src="assets/js/christmas-i18n.js?v=20261128"></script>
  <script defer src="assets/js/i18n.js?v=20261126"></script>
  <script defer src="assets/js/site.js?v=20261126"></script>
  <script defer src="assets/js/christmas.js?v=20261127"></script>
</head>
<body class="xmas-page">
  <a class="skip-link" href="<?= h($page) ?>#main">Skip to content</a>
  <header class="site-header" data-header><div class="nav-wrap">
    <a class="brand" href="./" aria-label="Chelart home"><img src="assets/img/chelart-logo-192.png" alt="Chelart Construction logo" width="192" height="192" srcset="assets/img/chelart-logo-192.png 192w" sizes="96px"></a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="Open menu"><span></span><span></span></button>
    <nav class="primary-nav" id="primary-nav" aria-label="Primary navigation">
      <a href="./#services">Services</a><a href="./#work">Our work</a><a href="./#about">About</a><a href="./#clients">Reviews</a>
      <div class="language-switch" role="group" aria-label="Select language"><button type="button" data-language-switch="en">EN</button><span aria-hidden="true">/</span><button type="button" data-language-switch="es">ES</button></div>
      <a class="nav-cta" href="<?= h($page) ?>#christmas-quote">Get a free quote <span aria-hidden="true">→</span></a>
    </nav>
  </div></header>

  <main id="main">
    <section class="hero xmas-hero" aria-labelledby="xmas-title">
      <div class="hero-copy">
        <p class="eyebrow reveal">Holiday services</p>
        <?php if ($promoActive): ?><p class="xmas-promo-chip reveal"><strong>Black Friday special</strong><span><?= $discount ?>% <em>OFF</em></span></p><?php endif; ?>
        <h1 id="xmas-title" class="xmas-title reveal"><span>Christmas</span> <em>Decoration</em> <span>Service</span></h1>
        <p class="hero-lede reveal">Make your home shine this Christmas without the stress. We handle the installation, maintenance and removal of your holiday decorations.</p>
        <ul class="xmas-benefits reveal" aria-label="What’s included">
          <li><svg viewBox="0 0 40 40" aria-hidden="true"><path d="M3 10c10 9 24 9 34 0"/><path d="M10 15.2v3M20 17.6v3M30 15.2v3"/><path d="M10 18.2c-2.6 0-4 2.3-4 4.6 0 2.4 1.8 4.2 4 4.2s4-1.8 4-4.2c0-2.3-1.4-4.6-4-4.6zM20 20.6c-2.6 0-4 2.3-4 4.6 0 2.4 1.8 4.2 4 4.2s4-1.8 4-4.2c0-2.3-1.4-4.6-4-4.6zM30 18.2c-2.6 0-4 2.3-4 4.6 0 2.4 1.8 4.2 4 4.2s4-1.8 4-4.2c0-2.3-1.4-4.6-4-4.6z"/></svg><span>Professional Installation</span></li>
          <li><svg viewBox="0 0 40 40" aria-hidden="true"><path d="M27.5 4.5a6.5 6.5 0 0 0-6.2 8.6L6.6 27.8a3 3 0 1 0 4.2 4.2l14.7-14.7a6.5 6.5 0 0 0 8.4-8.2l-4 4-3.6-.6-.6-3.6 4-4a6.5 6.5 0 0 0-2.2-.4z"/><path d="m6 6 3.5-1.5 2 2-1.5 3.5L22 22M24 24l9 9a2.5 2.5 0 0 1-3.5 3.5l-9-9"/></svg><span>Seasonal Maintenance</span></li>
          <li><svg viewBox="0 0 40 40" aria-hidden="true"><rect x="5" y="8" width="30" height="27" rx="2"/><path d="M5 15h30M13 4v7M27 4v7"/><path d="M11 21h3v3h-3zM18.5 21h3v3h-3zM26 21h3v3h-3zM11 28h3v3h-3zM18.5 28h3v3h-3z"/></svg><span>Post-Holiday Removal</span></li>
        </ul>
        <div class="hero-actions reveal"><a class="button button-primary xmas-cta" href="<?= h($page) ?>#christmas-quote">Book your Christmas service <span aria-hidden="true">→</span></a></div>
      </div>
      <div class="hero-visual xmas-visual<?= $heroIsSeasonal ? '' : ' is-placeholder' ?><?= $promoActive ? ' has-offer' : '' ?>">
        <?php if (!$heroIsSeasonal): ?><!-- REEMPLAZAR FOTO: guardar como assets/img/christmas/christmas-home-hero.jpg (+ -480, -800, -1200, -1600 .jpg) y la página la usa sola. --><?php endif; ?>
        <img src="<?= h($heroBase) ?>-800.jpg" alt="<?= $heroIsSeasonal ? 'Florida home decorated for Christmas with warm LED lights on the roofline, lit palm trees and wreaths' : 'Modern Florida home with a covered living area and pool' ?>" fetchpriority="high" width="<?= (int)$heroInfo[0] ?>" height="<?= (int)$heroInfo[1] ?>" srcset="<?= h(implode(', ', $heroSources)) ?>" sizes="(max-width: 700px) 100vw, 64vw">
        <?php if ($promoActive): ?>
          <div class="xmas-badge" aria-hidden="true">
            <svg class="xmas-bow" viewBox="0 0 140 110"><defs><linearGradient id="xmas-red" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ef3b3b"/><stop offset=".55" stop-color="#c3141c"/><stop offset="1" stop-color="#7d0a10"/></linearGradient></defs>
              <path d="M64 52 30 104l14-4 6 10 20-50z" fill="url(#xmas-red)"/><path d="M72 52l28 50 3-12 13 2-34-44z" fill="url(#xmas-red)"/>
              <path d="M66 48C46 20 10 14 8 34c-2 18 32 22 58 14z" fill="url(#xmas-red)"/><path d="M74 48c18-30 56-38 60-18 4 18-30 26-60 18z" fill="url(#xmas-red)"/>
              <path d="M24 32c14 0 28 6 38 13M118 28c-14 2-28 9-38 17" fill="none" stroke="#5e070b" stroke-opacity=".45" stroke-width="2"/>
              <rect x="60" y="40" width="20" height="16" rx="6" fill="#a50f17"/></svg>
            <span class="xmas-badge-tag"><span>Black</span> <span>Friday</span> <strong>Special</strong></span>
          </div>
          <aside class="xmas-offer" aria-label="Black Friday special">
            <p class="xmas-offer-value"><?= $discount ?>% <span>OFF</span></p>
            <p class="xmas-offer-text">When you book your Christmas installation and removal package before December 1st.</p>
          </aside>
        <?php endif; ?>
      </div>
    </section>

    <nav class="xmas-strip" aria-label="Christmas packages">
      <a href="<?= h($page) ?>?package=essential#christmas-quote" data-package-link="essential"><svg viewBox="0 0 40 40" aria-hidden="true"><path d="M5 19 20 6l15 13"/><path d="M9 16v18h22V16"/><path d="M17 34V24h6v10"/></svg><span><strong>Essential Christmas</strong><small>Exterior lights installation and removal.</small></span></a>
      <a href="<?= h($page) ?>?package=premium#christmas-quote" data-package-link="premium"><svg viewBox="0 0 40 40" aria-hidden="true"><path d="M13 5 6 18h4l-5 9h16l-5-9h4zM13 27v6"/><path d="M27 9l-6 11h3l-4 8h14l-4-8h3zM27 28v5"/><path d="M3 34h34"/></svg><span><strong>Premium Christmas</strong><small>Full exterior decoration, façade, garden and removal.</small></span></a>
      <a href="<?= h($page) ?>?package=luxury#christmas-quote" data-package-link="luxury"><svg viewBox="0 0 40 40" aria-hidden="true"><path d="M11 8h18l7 9-16 16L4 17z"/><path d="M4 17h32M16 8l-3 9 7 16 7-16-3-9"/></svg><span><strong>Luxury Christmas</strong><small>Custom design, complete interior &amp; exterior decoration, maintenance and removal.</small></span></a>
    </nav>

    <section class="section xmas-scope" aria-labelledby="scope-title">
      <div class="section-heading reveal"><p class="eyebrow">One team, the whole season</p><h2 id="scope-title">From the first light<br>to the last box.</h2>
        <p class="section-lede">Holiday light installation across Central Florida, handled end to end so you can simply enjoy it.</p></div>
      <ol class="xmas-steps">
        <li class="reveal"><span>01</span><h3>Professional installation</h3><p>Lighting and Christmas decoration installed safely by our team.</p></li>
        <li class="reveal"><span>02</span><h3>Every space</h3><p>Facades, rooflines, gardens, palm trees and indoor spaces.</p></li>
        <li class="reveal"><span>03</span><h3>Seasonal maintenance</h3><p>Checks and adjustments throughout the Christmas season.</p></li>
        <li class="reveal"><span>04</span><h3>Post-holiday removal</h3><p>We take everything down after Christmas and leave your home tidy.</p></li>
      </ol>
    </section>

    <section class="section xmas-packages" id="packages" aria-labelledby="packages-title">
      <div class="section-heading reveal"><p class="eyebrow">Christmas packages</p><h2 id="packages-title">Choose how much<br>you want to shine.</h2>
        <p class="section-lede">Every home is different, so every package is quoted individually after we review your property.</p></div>
      <div class="xmas-package-grid">
        <?php foreach (CHRISTMAS_PACKAGE_FEATURES as $key => $features): ?>
          <article class="xmas-package reveal<?= $key === 'premium' ? ' is-featured' : '' ?>" data-package-card="<?= h($key) ?>">
            <p class="xmas-package-tier"><?= ['essential' => '01', 'premium' => '02', 'luxury' => '03'][$key] ?></p>
            <h3><?= h(CHRISTMAS_PACKAGES[$key]['en']) ?></h3>
            <ul><?php foreach ($features as $feature): ?><li><?= h($feature) ?></li><?php endforeach; ?></ul>
            <p class="xmas-package-price">Personalized quote</p>
            <a class="button <?= $key === 'premium' ? 'button-primary' : 'button-outline' ?>" href="<?= h($page) ?>?package=<?= h($key) ?>#christmas-quote" data-package-link="<?= h($key) ?>">Request a quote <span aria-hidden="true">→</span></a>
          </article>
        <?php endforeach; ?>
      </div>
      <p class="xmas-package-note reveal">Need something different? Choose “Custom Service” in the form and tell us what you have in mind.</p>
    </section>

    <section class="xmas-quote-section" id="christmas-quote" aria-labelledby="xmas-form-title">
      <aside class="quote-aside xmas-quote-aside">
        <p class="eyebrow">Christmas decoration quote request</p>
        <h2 class="xmas-aside-title">Tell us about<br>your home.</h2>
        <p>Share the property details and the areas you’d like to decorate. Our team reviews every request and contacts you with a personalized quote.</p>
        <?php if ($promoActive): ?><p class="xmas-aside-promo"><strong><?= $discount ?>% <em>OFF</em></strong><span>When you book your Christmas installation and removal package before December 1st.</span></p><?php endif; ?>
        <div class="aside-contact"><span>Prefer to talk?</span><a href="tel:+16892509674">(689) 250-9674</a></div>
      </aside>
      <div class="quote-form-panel xmas-form-panel">
        <div class="quote-form-head"><div><p class="eyebrow">Free quote · no commitment</p><h2 id="xmas-form-title">Christmas Decoration Quote Request</h2></div></div>
        <form id="christmas-form" class="xmas-form" action="api/christmas-quote.php" method="post" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
          <input type="hidden" name="language" value="en" data-language-field>
          <div class="form-trap" aria-hidden="true"><label for="contact_note_internal">Leave this field empty</label><input id="contact_note_internal" type="text" name="contact_note_internal" tabindex="-1" autocomplete="new-password"></div>

          <fieldset class="xmas-fieldset">
            <legend>Customer information</legend>
            <div class="field-grid">
              <label class="field field-wide"><span>Full name <b aria-hidden="true">*</b></span><input name="full_name" type="text" maxlength="120" minlength="2" autocomplete="name" required></label>
              <label class="field"><span>Email address <b aria-hidden="true">*</b></span><input name="email" type="email" maxlength="254" autocomplete="email" inputmode="email" required></label>
              <label class="field"><span>Phone number <b aria-hidden="true">*</b></span><input name="phone" type="tel" maxlength="40" autocomplete="tel" inputmode="tel" placeholder="(407) 555-0123" required></label>
            </div>
            <div class="xmas-group" role="radiogroup" aria-labelledby="contact-method-label"><p class="xmas-group-label" id="contact-method-label">Preferred contact method <b aria-hidden="true">*</b></p>
              <div class="xmas-chips"><?php christmas_choice_chips('contact_method', CHRISTMAS_CONTACT_METHODS, 'radio', true); ?></div></div>
          </fieldset>

          <fieldset class="xmas-fieldset">
            <legend>Service location</legend>
            <div class="field-grid">
              <label class="field field-wide"><span>Property address <b aria-hidden="true">*</b></span><input name="property_address" type="text" maxlength="200" minlength="5" autocomplete="street-address" placeholder="Street and number" required></label>
              <label class="field"><span>City <b aria-hidden="true">*</b></span><input name="city" type="text" maxlength="100" autocomplete="address-level2" placeholder="e.g. DeBary" required></label>
              <label class="field"><span>ZIP code <b aria-hidden="true">*</b></span><input name="zip_code" type="text" inputmode="numeric" pattern="\d{5}(-\d{4})?" maxlength="10" autocomplete="postal-code" placeholder="e.g. 32713" required></label>
              <label class="field field-wide"><span>Property type <b aria-hidden="true">*</b></span><select name="property_type" required><option value="">Choose one</option><?php foreach (CHRISTMAS_PROPERTY_TYPES as $key => $labels): ?><option value="<?= h($key) ?>"><?= h($labels['en']) ?></option><?php endforeach; ?></select></label>
            </div>
          </fieldset>

          <fieldset class="xmas-fieldset">
            <legend>Service details</legend>
            <div class="xmas-group" role="radiogroup" aria-labelledby="package-label"><p class="xmas-group-label" id="package-label">Package selection <b aria-hidden="true">*</b></p>
              <div class="xmas-package-options">
                <?php $first = true; foreach (CHRISTMAS_PACKAGES as $key => $labels): ?>
                  <label class="choice-card"><input type="radio" name="package_type" value="<?= h($key) ?>"<?= $first ? ' required' : '' ?><?= $selectedPackage === $key ? ' checked' : '' ?>><span class="choice-text"><strong><?= h($labels['en']) ?></strong><small><?= h($key === 'custom' ? 'Tell us what you have in mind.' : CHRISTMAS_PACKAGE_FEATURES[$key][0]) ?></small></span></label>
                <?php $first = false; endforeach; ?>
              </div></div>
            <div class="xmas-group" role="group" aria-labelledby="areas-label"><p class="xmas-group-label" id="areas-label">Decoration areas <b aria-hidden="true">*</b> <small>Choose all that apply.</small></p>
              <div class="xmas-chips"><?php christmas_choice_chips('decoration_areas', CHRISTMAS_DECORATION_AREAS, 'checkbox'); ?></div></div>
            <div class="xmas-group" role="radiogroup" aria-labelledby="supplier-label"><p class="xmas-group-label" id="supplier-label">Who supplies the decorations? <b aria-hidden="true">*</b></p>
              <div class="xmas-chips"><?php christmas_choice_chips('decoration_supplier', CHRISTMAS_SUPPLIERS, 'radio', true); ?></div></div>
            <div class="xmas-group" role="radiogroup" aria-labelledby="size-label"><p class="xmas-group-label" id="size-label">Approximate property size <b aria-hidden="true">*</b></p>
              <div class="xmas-chips"><?php christmas_choice_chips('property_size', CHRISTMAS_PROPERTY_SIZES, 'radio', true); ?></div></div>
            <div class="field-grid xmas-dates">
              <label class="field"><span>Preferred installation date <i>Optional</i></span><input name="installation_date" type="date" min="<?= h($today) ?>" max="<?= h($maxDate) ?>"></label>
              <label class="field"><span>Preferred removal date <i>Optional</i></span><input name="removal_date" type="date" min="<?= h($today) ?>" max="<?= h($maxDate) ?>"></label>
              <label class="field field-wide"><span>Additional details <i>Optional</i></span><textarea name="additional_details" rows="4" maxlength="3000" placeholder="Colors, style, number of trees or palms, access to the roof, HOA rules…"></textarea></label>
            </div>
          </fieldset>

          <fieldset class="xmas-fieldset">
            <legend>Photos of your home</legend>
            <p class="step-help">Optional. Add up to 6 photos of the house and the areas you’d like to decorate (JPG, PNG or WebP, up to 8 MB each).</p>
            <label class="xmas-upload"><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple data-photo-input><span class="xmas-upload-icon" aria-hidden="true">+</span><span class="xmas-upload-text"><strong>Add photos</strong><small>Tap to choose from your phone or computer</small></span></label>
            <ul class="xmas-photo-list" data-photo-list aria-live="polite"></ul>
          </fieldset>

          <fieldset class="xmas-fieldset">
            <legend>Authorization</legend>
            <details class="xmas-privacy"><summary>Privacy notice</summary>
              <!-- PENDIENTE: validar este aviso con Chelart o enlazar su política de privacidad oficial cuando exista. -->
              <p>Chelart Construction LLC uses the information and photos in this form only to review your request, prepare your quote and contact you about this service. We don’t sell your information. Photos are stored privately and viewed only by the Chelart team. To update or delete your information, email chelartusa@gmail.com.</p>
            </details>
            <label class="consent"><input type="checkbox" name="privacy_consent" value="1" required><span>I accept the privacy notice and authorize CHELART to contact me by phone, WhatsApp or email about this request.</span></label>
            <p class="privacy-note">Please don’t include payment details or other sensitive information. No payment is required to request a quote.</p>
          </fieldset>

          <div class="form-error" data-form-error role="alert" tabindex="-1" hidden></div>
          <div class="form-controls"><button class="button button-primary xmas-submit" type="submit" data-submit>Submit my Christmas quote request <span aria-hidden="true">→</span></button></div>
          <p class="form-footnote">Prices are quoted individually after our team reviews your request. Submitting this form does not create a charge or a final price.</p>
        </form>
      </div>
    </section>

    <section class="closing-cta xmas-closing"><div class="closing-copy reveal"><p class="eyebrow">Christmas lights installation · DeBary · Orlando</p><h2>Serving homes across<br>Central Florida.</h2>
      <p>DeBary, Deltona, Sanford, Lake Mary, Orlando and nearby communities. Hablamos español.</p>
      <div class="closing-actions"><a class="button button-primary" href="<?= h($page) ?>#christmas-quote">Book your Christmas service <span aria-hidden="true">→</span></a><a class="closing-phone" href="tel:+16892509674">Or call (689) 250-9674</a></div></div></section>
  </main>
  <footer class="site-footer"><div class="footer-top"><a class="footer-brand" href="./">Chelart Construction<span>Repairs · Remodeling · Interior design</span></a>
    <div class="footer-contact"><a href="mailto:chelartusa@gmail.com">chelartusa@gmail.com</a><a href="tel:+16892509674">(689) 250-9674</a></div>
    <a class="text-link" href="https://www.instagram.com/chelart_construction" target="_blank" rel="noopener">Instagram <span aria-hidden="true">↗</span></a></div>
    <div class="footer-bottom"><span>© 2026 Chelart Construction LLC. All rights reserved.</span><span>DeBary · Orlando · Central Florida</span><a href="quote.php">Get a free quote →</a></div></footer>
  <nav class="mobile-cta" aria-label="Quick contact"><a href="tel:+16892509674">Call</a><a href="<?= h($page) ?>#christmas-quote">Book</a></nav>
</body></html>
