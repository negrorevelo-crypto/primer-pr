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
  <meta name="description" content="Repairs, kitchen and bath remodeling and interior design for homes and businesses in Sanford, Orlando and Central Florida. Hablamos español.">
  <link rel="canonical" href="https://chelart.com/">
  <meta property="og:type" content="website"><meta property="og:url" content="https://chelart.com/">
  <meta property="og:title" content="Chelart Construction | Repairs, Remodeling & Interior Design in Central Florida">
  <meta property="og:description" content="Repairs, kitchen and bath remodeling and interior design for homes and businesses in Sanford, Orlando and Central Florida. Hablamos español.">
  <meta property="og:image" content="https://chelart.com/assets/img/og-kitchen-project.jpg">
  <meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="Chelart Construction | Repairs, Remodeling & Interior Design in Central Florida">
  <meta name="twitter:description" content="Repairs, kitchen and bath remodeling and interior design for homes and businesses in Sanford, Orlando and Central Florida. Hablamos español.">
  <meta name="twitter:image" content="https://chelart.com/assets/img/og-kitchen-project.jpg">
  <link rel="icon" type="image/png" sizes="32x32" href="favicon-32.png">
  <link rel="apple-touch-icon" sizes="180x180" href="favicon-180.png">
  <link rel="icon" type="image/png" sizes="512x512" href="favicon-512.png">
  <title>Chelart Construction | Repairs, Remodeling & Interior Design in Central Florida</title>
  <script type="application/ld+json">{"@context":"https://schema.org","@type":"HomeAndConstructionBusiness","name":"Chelart Construction LLC","url":"https://chelart.com/","image":"https://chelart.com/assets/img/og-kitchen-project.jpg","logo":"https://chelart.com/assets/img/chelart-logo-740.png","telephone":"+1-689-250-9674","email":"chelartusa@gmail.com","address":{"@type":"PostalAddress","addressLocality":"Sanford","addressRegion":"FL","addressCountry":"US"},"areaServed":[{"@type":"City","name":"Sanford, FL"},{"@type":"AdministrativeArea","name":"Seminole County, FL"},{"@type":"City","name":"Orlando, FL"},{"@type":"AdministrativeArea","name":"Central Florida"}],"sameAs":["https://www.instagram.com/chelart_construction/"],"knowsLanguage":["en","es"]}</script>
  <script src="assets/js/language-init.js?v=20261124"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/site.css?v=20261124"><script defer src="assets/js/i18n.js?v=20261124"></script><script defer src="assets/js/site.js?v=20261124"></script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header" data-header><div class="nav-wrap">
    <a class="brand" href="./" aria-label="Chelart home"><img src="assets/img/chelart-logo-192.png" alt="Chelart Construction logo" width="192" height="192" srcset="assets/img/chelart-logo-192.png 192w" sizes="96px"></a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="Open menu"><span></span><span></span></button>
    <nav class="primary-nav" id="primary-nav" aria-label="Primary navigation">
      <a href="#services">Services</a><a href="#work">Our work</a><a href="#about">About</a><a href="#clients">Reviews</a>
      <div class="language-switch" role="group" aria-label="Select language"><button type="button" data-language-switch="en">EN</button><span aria-hidden="true">/</span><button type="button" data-language-switch="es">ES</button></div>
      <a class="nav-cta" href="quote.php">Get a free quote <span aria-hidden="true">→</span></a>
    </nav>
  </div></header>

  <main id="main">
    <section class="hero" aria-labelledby="hero-title"><div class="hero-copy">
      <p class="eyebrow reveal">Repairs · remodeling · interior design</p>
      <h1 id="hero-title" class="reveal">Your home,<br><em>thoughtfully</em><br>cared for.</h1>
      <p class="hero-lede reveal">Repairs, remodeling and interior design for homes and businesses in Central Florida.</p>
      <p class="hero-location reveal"><span class="location-dot"></span> Sanford · Orlando · Central Florida</p>
      <div class="hero-actions reveal"><!-- PENDIENTE: confirmar si la cotización es gratuita. Si no, el botón dice "Get a quote →" -->
        <a class="button button-primary" href="quote.php">Get a free quote <span aria-hidden="true">→</span></a><a class="text-link" href="#work">See our work <span aria-hidden="true">↓</span></a></div>
    </div><div class="hero-visual">
      <img src="assets/img/projects/hero-florida-home.jpg" alt="Modern Florida home with a covered living area and pool" fetchpriority="high" width="1672" height="941" srcset="assets/img/projects/hero-florida-home-480.jpg 480w, assets/img/projects/hero-florida-home-800.jpg 800w, assets/img/projects/hero-florida-home-1200.jpg 1200w, assets/img/projects/hero-florida-home-1600.jpg 1600w, assets/img/projects/hero-florida-home.jpg 1672w" sizes="(max-width: 700px) 100vw, 55vw">
    </div></section>

    <section class="intro-band reveal" aria-label="Chelart service area and qualifications">
      <p>Considered care for every part of your home.</p><span>Tell us what your home needs — we’ll take it from there.</span>
      <!-- PENDIENTE: "Licensed & insured · FL Lic. #[número]" --><!-- PENDIENTE: "[N] years serving Central Florida" -->
    </section>

    <section class="section services-section" id="services"><div class="section-heading reveal">
      <p class="eyebrow">Services</p><h2>From the small fixes<br>to the bigger picture.</h2>
      <p class="section-lede">Repairs, remodeling and interior design for homes and businesses in Central Florida.</p>
    </div><div class="service-grid">
      <article class="service-card reveal"><img class="service-card-photo" data-replace="Juan o un miembro del equipo haciendo una reparación en una casa de Florida (carpintería o pintura), plano medio" src="assets/img/services/handyman-tools.jpg" alt="Handyman tools ready for a home repair" loading="lazy" width="1024" height="768" srcset="assets/img/services/handyman-tools-480.jpg 480w, assets/img/services/handyman-tools-800.jpg 800w" sizes="(max-width: 700px) calc(100vw - 40px), 25vw">
        <!-- REEMPLAZAR FOTO: Juan o un miembro del equipo haciendo una reparación en una casa de Florida (carpintería o pintura), plano medio -->
        <div class="service-card-body"><h3>Repairs & maintenance</h3><p>Handyman work, carpentry, painting, fixture updates and general upkeep.</p><a href="quote.php?service=repairs" class="card-link">Request this service <span aria-hidden="true">→</span></a></div></article>
      <article class="service-card reveal"><img class="service-card-photo" src="assets/img/services/modern-kitchen-remodel-800.jpg" alt="Modern kitchen with wood cabinets and a white island" loading="lazy" width="800" height="452" srcset="assets/img/services/modern-kitchen-remodel-480.jpg 480w, assets/img/services/modern-kitchen-remodel-800.jpg 800w, assets/img/services/modern-kitchen-remodel-1200.jpg 1200w" sizes="(max-width: 700px) calc(100vw - 40px), 25vw">
        <div class="service-card-body"><h3>Kitchen & bath remodeling</h3><p>Full remodels that make the rooms you use most work better and look their best.</p><a href="quote.php?service=remodeling" class="card-link">Request this service <span aria-hidden="true">→</span></a></div></article>
      <article class="service-card reveal"><img class="service-card-photo" src="assets/img/projects/deBary-girls-spa-hd-800.jpg" alt="Interior of the girls’ spa remodeled in DeBary" loading="lazy" width="800" height="1052" srcset="assets/img/projects/deBary-girls-spa-hd-480.jpg 480w, assets/img/projects/deBary-girls-spa-hd-800.jpg 800w, assets/img/projects/deBary-girls-spa-hd-1094.jpg 1094w" sizes="(max-width: 700px) calc(100vw - 40px), 25vw">
        <div class="service-card-body"><h3>Interior design & space planning</h3><p>Design and space planning for homes and commercial spaces, from the first idea to the finished room.</p><a href="quote.php?service=design" class="card-link">Request this service <span aria-hidden="true">→</span></a></div></article>
      <article class="service-card service-card-dark reveal"><img class="service-card-photo" src="assets/img/projects/kitchen-lighting-installation-hd-800.jpg" alt="Decorative lighting installed above a kitchen island" loading="lazy" width="800" height="600" srcset="assets/img/projects/kitchen-lighting-installation-hd-480.jpg 480w, assets/img/projects/kitchen-lighting-installation-hd-800.jpg 800w, assets/img/projects/kitchen-lighting-installation-hd-1200.jpg 1200w, assets/img/projects/kitchen-lighting-installation-hd-1448.jpg 1448w" sizes="(max-width: 700px) calc(100vw - 40px), 25vw">
        <div class="service-card-body"><h3>Lighting & installations</h3><p>Decorative lighting, fixtures and home EV charger installation.</p><a href="quote.php?service=installations" class="card-link">Request this service <span aria-hidden="true">→</span></a></div></article>
    </div></section>

    <section class="process-section" aria-labelledby="process-title"><div class="process-photo reveal"><img src="assets/img/projects/process-solar-installation-800.jpg" alt="Chelart team planning a solar panel installation on a Florida home" loading="lazy" width="800" height="600" srcset="assets/img/projects/process-solar-installation-480.jpg 480w, assets/img/projects/process-solar-installation-800.jpg 800w" sizes="(max-width: 700px) 100vw, 45vw"></div>
      <div class="process-copy"><p class="eyebrow reveal">A clear path forward</p><h2 id="process-title" class="reveal">Good work begins<br>with listening.</h2>
        <p class="process-lede reveal">Tell us about your property and what you have in mind. We’ll understand the scope, talk through the next steps and keep communication clear as the work moves ahead.</p>
        <ol class="process-list"><li class="reveal"><span>01</span><strong>Consultation</strong><small>Share the property, needs and priorities.</small></li><li class="reveal"><span>02</span><strong>Plan</strong><small>Agree on a practical scope and next steps.</small></li><li class="reveal"><span>03</span><strong>Craft</strong><small>Complete the work with care and updates.</small></li><li class="reveal"><span>04</span><strong>Walkthrough</strong><small>Review the result together.</small></li></ol>
        <a class="button button-outline reveal" href="quote.php">Get a free quote <span aria-hidden="true">→</span></a></div>
    </section>

    <section class="section work-section" id="work"><div class="work-heading reveal"><div><p class="eyebrow">Chelart projects · real work</p><h2>Spaces made to<br>work beautifully.</h2></div></div>
      <div class="work-grid project-grid">
        <article class="work-card project-card reveal"><img src="assets/img/projects/deBary-girls-spa-hd-800.jpg" alt="Interior of the girls’ spa remodeled in DeBary" loading="lazy" width="800" height="1052" srcset="assets/img/projects/deBary-girls-spa-hd-480.jpg 480w, assets/img/projects/deBary-girls-spa-hd-800.jpg 800w, assets/img/projects/deBary-girls-spa-hd-1094.jpg 1094w" sizes="(max-width: 700px) calc(100vw - 40px), 33vw"><!-- REEMPLAZAR FOTO: versión en alta resolución, mínimo 1600 px --><div class="work-caption"><div><span>Commercial · DeBary, FL</span><h3>Girls’ day spa</h3><p>Full remodel and interior design for a bright, welcoming space.</p></div></div></article>
        <article class="work-card project-card reveal"><img src="assets/img/services/modern-kitchen-remodel-800.jpg" alt="Modern kitchen with wood cabinets and a white island" loading="lazy" width="800" height="452" srcset="assets/img/services/modern-kitchen-remodel-480.jpg 480w, assets/img/services/modern-kitchen-remodel-800.jpg 800w, assets/img/services/modern-kitchen-remodel-1200.jpg 1200w" sizes="(max-width: 700px) calc(100vw - 40px), 33vw"><!-- PENDIENTE: ciudad del proyecto --><div class="work-caption"><div><span>Kitchen</span><h3>Kitchen remodel</h3><p>A kitchen rebuilt around how the family cooks and gathers.</p></div></div></article>
        <article class="work-card project-card reveal"><img class="project-photo-contained" src="assets/img/projects/bathroom-remodel-hd-800.jpg" alt="Chelart bathroom remodeling project" loading="lazy" width="800" height="1061" srcset="assets/img/projects/bathroom-remodel-hd-480.jpg 480w, assets/img/projects/bathroom-remodel-hd-800.jpg 800w, assets/img/projects/bathroom-remodel-hd-1089.jpg 1089w" sizes="(max-width: 700px) calc(100vw - 40px), 33vw"><!-- REEMPLAZAR FOTO: versión en alta resolución, mínimo 1600 px --><!-- PENDIENTE: ciudad del proyecto --><div class="work-caption"><div><span>Bathroom</span><h3>Bathroom remodel</h3><p>A calmer, easier-to-clean bathroom with updated finishes.</p></div></div></article>
        <article class="work-card project-card reveal"><img src="assets/img/projects/kitchen-lighting-installation-hd-800.jpg" alt="Decorative lighting installed above a kitchen island" loading="lazy" width="800" height="600" srcset="assets/img/projects/kitchen-lighting-installation-hd-480.jpg 480w, assets/img/projects/kitchen-lighting-installation-hd-800.jpg 800w, assets/img/projects/kitchen-lighting-installation-hd-1200.jpg 1200w, assets/img/projects/kitchen-lighting-installation-hd-1448.jpg 1448w" sizes="(max-width: 700px) calc(100vw - 40px), 33vw"><!-- PENDIENTE: ciudad del proyecto --><div class="work-caption"><div><span>Lighting</span><h3>Kitchen lighting design</h3><p>Decorative lighting that makes the island the center of the room.</p></div></div></article>
        <article class="work-card project-card reveal"><img src="assets/img/projects/tesla-home-charging-installation-hd-800.jpg" alt="Home EV charger installed in a garage" loading="lazy" width="800" height="800" srcset="assets/img/projects/tesla-home-charging-installation-hd-480.jpg 480w, assets/img/projects/tesla-home-charging-installation-hd-800.jpg 800w, assets/img/projects/tesla-home-charging-installation-hd-1200.jpg 1200w, assets/img/projects/tesla-home-charging-installation-hd-1254.jpg 1254w" sizes="(max-width: 700px) calc(100vw - 40px), 33vw"><!-- PENDIENTE: ciudad del proyecto --><!-- PENDIENTE: confirmar "to code" --><div class="work-caption"><div><span>Installation</span><h3>Home EV charger</h3><p>Residential charger installation, clean and to code.</p></div></div></article>
        <article class="work-card project-card reveal"><img class="project-photo-contained" src="assets/img/projects/stairs-garden-remodel-hd-800.jpg" alt="Interior stairs opening toward a landscaped garden" loading="lazy" width="800" height="1059" srcset="assets/img/projects/stairs-garden-remodel-hd-480.jpg 480w, assets/img/projects/stairs-garden-remodel-hd-800.jpg 800w, assets/img/projects/stairs-garden-remodel-hd-1090.jpg 1090w" sizes="(max-width: 700px) calc(100vw - 40px), 33vw"><!-- REEMPLAZAR FOTO: versión en alta resolución, mínimo 1600 px --><!-- PENDIENTE: ciudad del proyecto --><div class="work-caption"><div><span>Stairs & garden</span><h3>Stairs & garden remodel</h3><p>A new transition between the house and the garden.</p></div></div></article>
        <article class="work-card project-card reveal project-featured"><div class="project-mosaic"><img src="assets/img/projects/country-home-exterior-day.jpg" alt="Country home in Colombia, exterior by day" loading="lazy" width="1024" height="500" srcset="assets/img/projects/country-home-exterior-day-480.jpg 480w, assets/img/projects/country-home-exterior-day-800.jpg 800w" sizes="(max-width: 700px) calc(100vw - 40px), 33vw"><img src="assets/img/projects/country-home-exterior-evening.jpg" alt="Country home in Colombia, exterior at evening" loading="lazy" width="1024" height="608" srcset="assets/img/projects/country-home-exterior-evening-480.jpg 480w, assets/img/projects/country-home-exterior-evening-800.jpg 800w" sizes="(max-width: 700px) calc(100vw - 40px), 33vw"><img src="assets/img/projects/country-home-kitchen.jpg" alt="Interior kitchen of the country home in Colombia" loading="lazy" width="1024" height="500" srcset="assets/img/projects/country-home-kitchen-480.jpg 480w, assets/img/projects/country-home-kitchen-800.jpg 800w" sizes="(max-width: 700px) calc(100vw - 40px), 33vw"><img src="assets/img/projects/country-home-patio-hd-800.jpg" alt="Outdoor living area at the country home in Colombia" loading="lazy" width="800" height="600" srcset="assets/img/projects/country-home-patio-hd-480.jpg 480w, assets/img/projects/country-home-patio-hd-800.jpg 800w, assets/img/projects/country-home-patio-hd-1200.jpg 1200w, assets/img/projects/country-home-patio-hd-1448.jpg 1448w" sizes="(max-width: 700px) calc(100vw - 40px), 33vw"></div><div class="work-caption"><div><span>Residential · Colombia</span><h3>Country home, design & build</h3><p>Designed and built from the ground up around its natural setting.</p></div></div></article>
      </div>
    </section>

    <section class="story-section" id="about"><div class="story-mark reveal"><!-- REEMPLAZAR FOTO: retrato de Juan en una obra o con el equipo, vertical 4:5, luz natural, ropa de trabajo con el logo si existe --><img data-replace="retrato de Juan en una obra o con el equipo, vertical 4:5, luz natural, ropa de trabajo con el logo si existe" src="assets/img/chelart-logo-740.png" alt="Chelart Construction logo" loading="lazy" width="740" height="740" srcset="assets/img/chelart-logo-740.png 740w" sizes="(max-width: 700px) 88vw, 370px"></div>
      <div class="story-copy reveal"><p class="eyebrow">About Chelart</p><h2>Design roots.<br>Hands-on care.</h2>
        <p>Chelart Construction began with a passion for interior design and spaces that work. Today Juan and his team bring that eye to repairs, remodels and design projects for homes and businesses across Central Florida, in English and in Spanish.</p>
        <!-- PENDIENTE: apellido de Juan y su rol (fundador / dueño); confirmar que el equipo atiende en español -->
        <a class="text-link" href="quote.php">Work with us <span aria-hidden="true">→</span></a></div></section>

    <section class="testimonial-section" id="clients" aria-labelledby="testimonial-title"><div class="testimonial-heading reveal"><p class="eyebrow">Client reviews</p><h2 id="testimonial-title">Their words say it best.</h2><p>Homeowners and business owners who have worked with Chelart.</p></div>
      <div class="testimonial-grid"><article class="testimonial-card reveal"><img src="assets/img/clients/client-diane-delgado.jpg" alt="Chelart client Diane Delgado" loading="lazy" width="1254" height="1254" srcset="assets/img/clients/client-diane-delgado-136.jpg 136w, assets/img/clients/client-diane-delgado-480.jpg 480w, assets/img/clients/client-diane-delgado-800.jpg 800w" sizes="68px"><div class="testimonial-stars" aria-label="5 out of 5 stars">★★★★★</div>
        <blockquote>“If I could award Chelart Construction with 10 stars, I think it still wouldn’t do justice. This company has completed numerous projects for both my home and business, always delivering on their promises with exceptional quality work at a reasonable price.”</blockquote><p class="quote-author">Diane Delgado <span>· Home & business client</span></p></article>
        <article class="testimonial-card reveal"><img src="assets/img/clients/client-coy-ingram.jpg" alt="Chelart client Coy Ingram" loading="lazy" width="1254" height="1254" srcset="assets/img/clients/client-coy-ingram-136.jpg 136w, assets/img/clients/client-coy-ingram-480.jpg 480w, assets/img/clients/client-coy-ingram-800.jpg 800w" sizes="68px"><div class="testimonial-stars" aria-label="5 out of 5 stars">★★★★★</div>
        <blockquote>“Professional, reliable, dedicated, talented, friendly and dependable service! I’m glad that I used Chelart on many projects. I have always been very satisfied with their work. Truly, I’m grateful for Juan and his team. They do magnificent work and are always available. I highly recommend their services! 10’s across the board!”</blockquote><p class="quote-author">Coy Ingram <span>· Repeat client</span></p></article></div>
      <!-- PENDIENTE: tercer testimonio con tipo de proyecto, o enlace a reseñas de Google --></section>

    <section class="closing-cta"><div class="closing-copy reveal"><h2>Let’s talk about<br>what you need.</h2><p>Share a few details and we’ll call you back to talk through the project and the next step.</p>
      <div class="closing-actions"><a class="button button-primary" href="quote.php">Get a free quote <span aria-hidden="true">→</span></a><a class="closing-phone" href="tel:+16892509674">Or call (689) 250-9674</a></div></div></section>
  </main>
  <footer class="site-footer"><div class="footer-top"><a class="footer-brand" href="./">Chelart Construction<span>Repairs · Remodeling · Interior design</span></a>
    <div class="footer-contact"><a href="mailto:chelartusa@gmail.com">chelartusa@gmail.com</a><a href="tel:+16892509674">(689) 250-9674</a><!-- PENDIENTE: correo con dominio propio, p. ej. hola@chelart… --></div>
    <a class="text-link" href="https://www.instagram.com/chelart_construction" target="_blank" rel="noopener">Instagram <span aria-hidden="true">↗</span></a></div>
    <div class="footer-bottom"><span>© 2026 Chelart Construction LLC. All rights reserved.</span><span>Sanford · Orlando · Central Florida</span><a href="quote.php">Get a free quote →</a></div></footer>
  <nav class="mobile-cta" aria-label="Quick contact"><a href="tel:+16892509674">Call</a><a href="quote.php">Quote</a></nav>
</body></html>
