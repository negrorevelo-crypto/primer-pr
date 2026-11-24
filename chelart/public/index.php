<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="<?= h(app_base_path()) ?>">
  <meta name="theme-color" content="#f6f3ee">
  <meta name="description" content="Thoughtful home care, repairs, maintenance and renovation for homes and businesses in Orlando and Central Florida.">
  <title>Chelart | Thoughtful Home Care & Renovation</title>
  <script src="assets/js/language-init.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/site.css">
  <script defer src="assets/js/i18n.js"></script>
  <script defer src="assets/js/site.js"></script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header" data-header>
    <div class="nav-wrap">
      <a class="brand" href="./" aria-label="Chelart home">
        <img src="assets/img/chelart-logo-transparent.png" alt="Chelart — We build a better space for a better life">
      </a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="Open menu">
        <span></span><span></span>
      </button>
      <nav class="primary-nav" id="primary-nav" aria-label="Primary navigation">
        <a href="#care">Home care</a>
        <a href="#services">Repairs & maintenance</a>
        <a href="#design">Design & renovation</a>
        <a href="#work">Our work</a>
        <a href="#about">About</a>
        <div class="language-switch" role="group" aria-label="Select language"><button type="button" data-language-switch="en">EN</button><span aria-hidden="true">/</span><button type="button" data-language-switch="es">ES</button></div>
        <a class="nav-cta" href="quote.php">Start a quote <span aria-hidden="true">↗</span></a>
      </nav>
    </div>
  </header>

  <main id="main">
    <section class="hero" aria-labelledby="hero-title">
      <div class="hero-copy">
        <p class="eyebrow reveal">Home care · repairs · design</p>
        <h1 id="hero-title" class="reveal">Your home,<br><em>thoughtfully</em><br>cared for.</h1>
        <p class="hero-lede reveal">Design, maintenance and renovations for the home you’ve worked hard to build.</p>
        <p class="hero-location reveal"><span class="location-dot"></span> Orlando · Central Florida</p>
        <div class="hero-actions reveal">
          <a class="button button-primary" href="quote.php">Start a quote <span aria-hidden="true">↗</span></a>
          <a class="text-link" href="#services">Explore services <span aria-hidden="true">↓</span></a>
        </div>
        <div class="hero-note reveal"><span class="note-rule"></span><span>One trusted team for the details your home needs.</span></div>
      </div>
      <div class="hero-visual" role="img" aria-label="Warm modern Florida home with natural stone, wood and palms">
        <img src="assets/img/hero-home-placeholder.png" alt="A modern Central Florida residence with warm natural finishes">
        <div class="visual-caption"><span>Central Florida</span><span>Thoughtful care, from the inside out</span></div>
      </div>
      <div class="hero-index" aria-hidden="true">01 <span></span> 04</div>
    </section>

    <section class="intro-band reveal" aria-label="Chelart services summary">
      <p>Considered care for every part of your home.</p>
      <span>Tell us what your home needs — we’ll take it from there.</span>
    </section>

    <section class="section services-section" id="care">
      <div class="section-heading reveal">
        <p class="eyebrow">A more considered way to care for home</p>
        <h2>From the small fixes<br>to the bigger picture.</h2>
        <p class="section-lede">One thoughtful point of contact for the work that keeps a home beautiful, useful and ready for what comes next.</p>
      </div>
      <div class="service-grid" id="services">
        <article class="service-card reveal">
          <img class="service-card-photo" src="assets/img/services/roof-gutter-maintenance.jpg" alt="Professional maintaining roof drainage on a home" loading="lazy">
          <div class="service-card-body">
          <span class="card-number">01</span>
          <h3>Home care</h3>
          <p>Planned upkeep and seasonal preparation to help your home stay in good shape. <span class="proposal-tag">Proposed plan</span></p>
          <a href="quote.php?service=home-care" class="card-link" aria-label="Ask about home care">Explore home care <span aria-hidden="true">↗</span></a>
          </div>
        </article>
        <article class="service-card reveal">
          <div class="service-card-photo-pair">
            <img src="assets/img/services/handyman-tools.jpg" alt="Handyman carrying a toolbox in a home" loading="lazy">
            <img src="assets/img/services/window-repair.jpg" alt="Technician repairing a residential window" loading="lazy">
          </div>
          <div class="service-card-body">
          <span class="card-number">02</span>
          <h3>Repairs & maintenance</h3>
          <p>Handyman work, carpentry, painting, fixture updates and general home maintenance.</p>
          <a href="quote.php?service=repairs" class="card-link" aria-label="Request repairs and maintenance">Tell us what needs fixing <span aria-hidden="true">↗</span></a>
          </div>
        </article>
        <article class="service-card reveal" id="design">
          <img class="service-card-photo" src="assets/img/services/window-installation.jpg" alt="Craftsman fitting a new window in a bright interior" loading="lazy">
          <div class="service-card-body">
          <span class="card-number">03</span>
          <h3>Design & renovation</h3>
          <p>Interior design, space planning and thoughtful updates for residential and commercial spaces.</p>
          <a href="quote.php?service=design" class="card-link" aria-label="Request design and renovation">Shape your next project <span aria-hidden="true">↗</span></a>
          </div>
        </article>
        <article class="service-card service-card-dark reveal">
          <div class="service-card-body">
          <span class="card-number">04</span>
          <span class="line-icon icon-route" aria-hidden="true"></span>
          <h3>Project oversight</h3>
          <p>Clear updates, trusted vendor coordination and a final walkthrough. <span class="proposal-tag proposal-tag-light">Proposed service</span></p>
          <a href="quote.php?service=project-oversight" class="card-link" aria-label="Ask about project oversight">Discuss your project <span aria-hidden="true">↗</span></a>
          </div>
        </article>
      </div>
      <p class="service-note">Home care plans and project oversight are proposed additions. Chelart will confirm availability before these are offered as active services.</p>
    </section>

    <section class="process-section" aria-labelledby="process-title">
      <div class="process-photo reveal">
        <img src="assets/img/services/kitchen-plumbing-repair.jpg" alt="Technician carrying out a plumbing repair in a bright kitchen" loading="lazy">
        <span class="photo-index">Craft, with care.</span>
      </div>
      <div class="process-copy">
        <p class="eyebrow reveal">A clear path forward</p>
        <h2 id="process-title" class="reveal">Good work begins<br>with listening.</h2>
        <p class="process-lede reveal">Tell us about your property and what you have in mind. We’ll understand the scope, talk through the next steps and keep communication clear as the work moves ahead.</p>
        <ol class="process-list">
          <li class="reveal"><span>01</span><strong>Consultation</strong><small>Share the property, needs and priorities.</small></li>
          <li class="reveal"><span>02</span><strong>Plan</strong><small>Agree on a practical scope and next steps.</small></li>
          <li class="reveal"><span>03</span><strong>Craft</strong><small>Complete the work with care and updates.</small></li>
          <li class="reveal"><span>04</span><strong>Walkthrough</strong><small>Review the result together.</small></li>
        </ol>
        <a class="button button-outline reveal" href="quote.php">Tell us about your project <span aria-hidden="true">↗</span></a>
      </div>
    </section>

    <section class="section work-section" id="work">
      <div class="work-heading reveal">
        <div><p class="eyebrow">Chelart projects · real work</p><h2>Spaces made to<br>work beautifully.</h2></div>
        <a href="quote.php" class="text-link">Start something new <span aria-hidden="true">↗</span></a>
      </div>
      <div class="work-grid project-grid">
        <article class="work-card project-card project-featured reveal">
          <div class="project-mosaic">
            <img src="assets/img/projects/country-home-exterior-day.jpg" alt="Country home in Colombia, exterior by day" loading="lazy">
            <img src="assets/img/projects/country-home-exterior-evening.jpg" alt="Country home in Colombia, exterior at evening" loading="lazy">
            <img src="assets/img/projects/country-home-kitchen.jpg" alt="Interior kitchen of the country home in Colombia" loading="lazy">
            <img src="assets/img/projects/country-home-patio.jpg" alt="Outdoor living area at the country home in Colombia" loading="lazy">
          </div>
          <div class="work-caption"><div><span>Residential · Colombia</span><h3>Country home design & construction</h3><p>Design, construction and interior décor shaped around its natural setting.</p></div></div>
        </article>
        <article class="work-card project-card reveal">
          <img class="project-photo-contained" src="assets/img/projects/deBary-girls-spa.jpg" alt="Interior of the girls’ spa remodeled in Debary" loading="lazy">
          <div class="work-caption"><div><span>Commercial · Debary</span><h3>Girls’ day spa</h3><p>Full remodel and interior design for a bright, welcoming space.</p></div></div>
        </article>
        <article class="work-card project-card reveal">
          <img src="assets/img/projects/tesla-home-charging-installation.jpg" alt="Tesla home charging station installation" loading="lazy">
          <div class="work-caption"><div><span>Installation</span><h3>Home charging station</h3><p>Residential charging installation.</p></div></div>
        </article>
        <article class="work-card project-card reveal">
          <img src="assets/img/projects/kitchen-lighting-installation.jpg" alt="Decorative lighting installed above a kitchen island" loading="lazy">
          <div class="work-caption"><div><span>Lighting · Kitchen</span><h3>Kitchen lighting & decorative design</h3><p>Illuminating your space, enhancing your lifestyle.</p></div></div>
        </article>
        <article class="work-card project-card reveal">
          <img src="assets/img/projects/kitchen-remodel.jpg" alt="Chelart kitchen remodeling project" loading="lazy">
          <div class="work-caption"><div><span>Renovation · Kitchen</span><h3>Kitchen remodeling</h3><p>Where function meets elegance to create the heart of your home.</p></div></div>
        </article>
        <article class="work-card project-card reveal">
          <img class="project-photo-contained" src="assets/img/projects/bathroom-remodel.jpg" alt="Chelart bathroom remodeling project" loading="lazy">
          <div class="work-caption"><div><span>Renovation · Bathroom</span><h3>Bathroom remodeling</h3><p>Transforming your bathroom into a sanctuary of comfort and style.</p></div></div>
        </article>
        <article class="work-card project-card reveal">
          <img class="project-photo-contained" src="assets/img/projects/stairs-garden-remodel.jpg" alt="Interior stairs opening toward a landscaped garden" loading="lazy">
          <div class="work-caption"><div><span>Residential · Stairs & garden</span><h3>Stairs & garden remodeling</h3><p>Crafting beautiful transitions between indoors and outdoors.</p></div></div>
        </article>
      </div>
      <p class="asset-note">Project photos are retained from Chelart’s existing portfolio. The country home project is in Colombia; other work examples are shown as presented in the original portfolio.</p>
    </section>

    <section class="story-section" id="about">
      <div class="story-mark reveal"><img src="assets/img/chelart-logo-transparent.png" alt="Chelart — We build a better space for a better life"></div>
      <div class="story-copy reveal">
        <p class="eyebrow">A story of spaces and care</p>
        <h2>Design roots.<br>Hands-on care.</h2>
        <p>Chelart began with a passion for interior design and creating functional spaces. Today, that experience supports a broader range of home and business needs — from repairs and maintenance to thoughtful renovations in Florida.</p>
        <a class="text-link" href="quote.php">Meet Chelart through your project <span aria-hidden="true">↗</span></a>
      </div>
    </section>

    <section class="testimonial-section" id="clients" aria-labelledby="testimonial-title">
      <div class="testimonial-heading reveal"><p class="eyebrow">Our satisfied clients</p><h2 id="testimonial-title">Their words say it best.</h2><p>Why clients trust Chelart for their home and business needs.</p></div>
      <div class="testimonial-grid">
        <article class="testimonial-card reveal">
          <img src="assets/img/clients/client-diane-delgado.jpg" alt="Chelart client Diane Delgado" loading="lazy">
          <div class="testimonial-stars" aria-label="5 out of 5 stars">★★★★★</div>
          <blockquote>“If I could award Chelart Construction with 10 stars, I think it still wouldn’t do justice. This company has completed numerous projects for both my home and business, always delivering on their promises with exceptional quality work at a reasonable price.”</blockquote>
          <p class="quote-author">Diane Delgado <span>· Chelart client</span></p>
        </article>
        <article class="testimonial-card reveal">
          <img src="assets/img/clients/client-coy-ingram.jpg" alt="Chelart client Coy Ingram" loading="lazy">
          <div class="testimonial-stars" aria-label="5 out of 5 stars">★★★★★</div>
          <blockquote>“Professional, reliable, dedicated, talented, friendly and dependable service! I’m glad that I used Chelart on many projects. I have always been very satisfied with their work. Truly, I’m grateful for Juan and his team. They do magnificent work and are always available. I highly recommend their services! 10’s across the board!”</blockquote>
          <p class="quote-author">Coy Ingram <span>· Chelart client</span></p>
        </article>
      </div>
    </section>

    <section class="closing-cta">
      <div class="closing-copy reveal">
        <p class="eyebrow">Your home, your next chapter</p>
        <h2>Let’s talk about<br>what you need.</h2>
        <p>Start with a few details. Chelart will follow up to understand the project and the right next step.</p>
        <a class="button button-primary" href="quote.php">Start a quote <span aria-hidden="true">↗</span></a>
      </div>
      <div class="closing-stamp reveal"><img src="assets/img/chelart-logo-transparent.png" alt="Chelart logo"></div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-top">
      <a class="footer-brand" href="./">CHELART<span>Home care · Repairs · Design</span></a>
      <p>Thoughtful care for homes<br>and businesses in Central Florida.</p>
      <div class="footer-contact"><a href="mailto:chelartusa@gmail.com">chelartusa@gmail.com</a><a href="tel:+16892509674">(689) 250-9674</a></div>
      <a class="text-link" href="https://www.instagram.com/chelart_construction" target="_blank" rel="noopener">Instagram <span aria-hidden="true">↗</span></a>
    </div>
    <div class="footer-bottom"><span>© <?= date('Y') ?> Chelart. All rights reserved.</span><span>Orlando · Central Florida</span><a href="quote.php">Request a quote</a></div>
  </footer>
</body>
</html>
