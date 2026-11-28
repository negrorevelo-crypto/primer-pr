<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

$service = trim((string)($_GET['service'] ?? ''));
$allowedServices = ['repairs', 'remodeling', 'design', 'installations'];
if (!in_array($service, $allowedServices, true)) {
    $service = '';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?= h(app_base_path()) ?>">
  <meta name="theme-color" content="#f6f3ee">
  <meta name="description" content="Request a quote for repairs, remodeling or interior design in Central Florida.">
  <link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png"><link rel="apple-touch-icon" sizes="180x180" href="favicon-180.png"><link rel="icon" type="image/png" sizes="512x512" href="favicon-512.png">
  <title>Chelart Construction | Repairs, Remodeling & Interior Design in Central Florida</title>
  <script src="assets/js/language-init.js?v=20261125"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/site.css?v=20261126">
  <script defer src="assets/js/i18n.js?v=20261126"></script>
  <script defer src="assets/js/site.js?v=20261126"></script>
</head>
<body class="quote-page">
  <header class="site-header quote-header">
    <div class="nav-wrap">
      <a class="brand" href="./" aria-label="Chelart home"><img src="assets/img/chelart-logo-192.png" alt="Chelart Construction logo" width="192" height="192" srcset="assets/img/chelart-logo-192.png 192w" sizes="96px"></a>
      <div class="quote-nav-actions">
        <div class="language-switch" role="group" aria-label="Select language"><button type="button" data-language-switch="en">EN</button><span aria-hidden="true">/</span><button type="button" data-language-switch="es">ES</button></div>
        <a class="text-link back-link" href="./">Home <span aria-hidden="true">←</span></a>
      </div>
    </div>
  </header>
  <main class="quote-layout">
    <aside class="quote-aside">
      <p class="eyebrow">A thoughtful first step</p>
      <h1>Tell us about<br>your home.</h1>
      <p>Share a few details about the property and what you have in mind. This isn’t an instant price calculator; it helps Chelart understand the scope and follow up with you.</p>
      <div class="quote-aside-note"><span class="note-rule"></span><span>DeBary · Orlando · Central Florida</span></div>
      <div class="aside-contact"><span>Prefer to talk?</span><a href="tel:+16892509674">(689) 250-9674</a></div>
    </aside>
    <section class="quote-form-panel" aria-labelledby="quote-form-title">
      <div class="quote-form-head">
        <div><p class="eyebrow">Project inquiry</p><h2 id="quote-form-title">Get a quote</h2></div>
        <span class="step-count" data-step-count>01 <span>/</span> 04</span>
      </div>
      <div class="progress-track" aria-hidden="true"><span data-progress></span></div>
      <form id="quote-form" action="api/quote.php" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="service_hint" value="<?= h($service) ?>">
        <div class="form-trap" aria-hidden="true"><label for="contact_note_internal">Leave this field empty</label><input id="contact_note_internal" type="text" name="contact_note_internal" tabindex="-1" autocomplete="new-password"></div>

        <fieldset class="form-step is-active" data-step="1">
          <legend>First, a little about the property.</legend>
          <p class="step-help">Where should we picture the work?</p>
          <div class="field-grid">
            <label class="field"><span>Property type</span><select name="property_type" required><option value="">Choose one</option><option value="single-family">Single-family home</option><option value="condo">Condo or townhome</option><option value="commercial">Commercial space</option><option value="other">Other</option></select></label>
            <label class="field"><span>City</span><input name="city" type="text" maxlength="100" placeholder="e.g. Sanford" required></label>
            <label class="field"><span>ZIP code</span><input name="postal_code" inputmode="numeric" pattern="\d{5}" maxlength="5" placeholder="e.g. 32801" required></label>
          </div>
          <p class="field-error" data-step-error hidden>Choose a property type.</p>
        </fieldset>

        <fieldset class="form-step" data-step="2">
          <legend>What can we help with?</legend>
          <p class="step-help">Choose all that apply.</p>
          <div class="choice-grid">
            <?php
            $serviceOptions = [
                'repairs' => ['Repairs & maintenance', 'Handyman work, carpentry, painting, fixture updates and general upkeep.'],
                'remodeling' => ['Kitchen & bath remodeling', 'Full kitchen and bathroom remodels.'],
                'design' => ['Interior design & space planning', 'Homes and commercial spaces.'],
                'installations' => ['Lighting & installations', 'Decorative lighting, fixtures and home EV chargers.'],
                'other' => ['Something else', 'Tell us more in the next step'],
            ];
            foreach ($serviceOptions as $key => [$label, $description]):
              $checked = ($service !== '' && $service === $key) ? ' checked' : '';
            ?>
              <label class="choice-card"><input type="checkbox" name="services[]" value="<?= h($key) ?>"<?= $checked ?>><span class="choice-check" aria-hidden="true"></span><span class="choice-text"><strong><?= h($label) ?></strong><small><?= h($description) ?></small></span></label>
            <?php endforeach; ?>
          </div>
          <p class="field-error" data-services-error hidden>Select at least one service.</p>
        </fieldset>

        <fieldset class="form-step" data-step="3">
          <legend>What does the project look like?</legend>
          <p class="step-help">A few notes help us prepare for the conversation.</p>
          <label class="field"><span>What would you like done?</span><textarea name="scope_description" rows="5" maxlength="3000" placeholder="Share the rooms, repairs or improvements you have in mind…" required></textarea></label>
          <div class="field-grid field-grid-2">
            <label class="field"><span>When would you like to begin?</span><select name="project_timeline" required><option value="">Choose a timeframe</option><option value="asap">As soon as possible</option><option value="within-month">Within a month</option><option value="one-to-three-months">1–3 months</option><option value="planning">I’m planning ahead</option></select></label>
            <label class="field"><span>Best way to reach you</span><select name="preferred_contact" required><option value="email">Email</option><option value="phone">Phone</option><option value="text">Text message</option></select></label>
          </div><p class="field-error" data-step-error hidden>Please complete the required project details.</p>
        </fieldset>

        <fieldset class="form-step" data-step="4">
          <legend>Where can we reach you?</legend>
          <p class="step-help">Chelart will use these details to respond to your quote request.</p>
          <div class="field-grid field-grid-2">
            <label class="field"><span>Your name</span><input name="full_name" type="text" maxlength="120" autocomplete="name" required></label>
            <label class="field"><span>Phone</span><input name="phone" type="tel" maxlength="40" autocomplete="tel" required></label>
            <label class="field field-wide"><span>Email</span><input name="email" type="email" maxlength="254" autocomplete="email" required></label>
          </div>
          <label class="consent"><input type="checkbox" name="consent" value="1" required><span>I agree that Chelart may use these details to respond to my project inquiry.</span></label>
          <p class="privacy-note">Please don’t include payment details or other sensitive information in this form.</p>
          <p class="field-error" data-step-error hidden>Please complete the required contact details and consent.</p>
        </fieldset>

        <div class="form-error" data-form-error role="alert" hidden></div>
        <div class="form-controls">
          <button class="button button-quiet" type="button" data-back hidden><span aria-hidden="true">←</span> Back</button>
          <button class="button button-primary" type="button" data-next>Continue <span aria-hidden="true">→</span></button>
          <button class="button button-primary" type="submit" data-submit hidden>Send inquiry <span aria-hidden="true">→</span></button>
        </div>
        <p class="form-footnote">Your request is private and will be reviewed by the Chelart team.</p>
      </form>
    </section>
  </main>
  <footer class="quote-footer"><a href="mailto:chelartusa@gmail.com">chelartusa@gmail.com</a><span>© 2026 Chelart Construction LLC</span><span>Central Florida</span></footer>
</body>
</html>
