(() => {
  const header = document.querySelector('[data-header]');
  const menuButton = document.querySelector('.menu-toggle');
  const nav = document.querySelector('#primary-nav');

  const updateHeader = () => {
    if (header) header.classList.toggle('is-scrolled', window.scrollY > 24);
  };
  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  if (menuButton && nav) {
    const closeMenu = () => {
      menuButton.setAttribute('aria-expanded', 'false');
      const spanish = document.documentElement.lang === 'es';
      menuButton.setAttribute('aria-label', spanish ? 'Abrir menú' : 'Open menu');
      nav.classList.remove('is-open');
      document.body.classList.remove('menu-open');
    };
    menuButton.addEventListener('click', () => {
      const open = menuButton.getAttribute('aria-expanded') !== 'true';
      menuButton.setAttribute('aria-expanded', String(open));
      const language = document.documentElement.lang === 'es';
      menuButton.setAttribute('aria-label', open
        ? (language ? 'Cerrar menú' : 'Close menu')
        : (language ? 'Abrir menú' : 'Open menu'));
      nav.classList.toggle('is-open', open);
      document.body.classList.toggle('menu-open', open);
    });
    nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
      closeMenu();
    }));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && menuButton.getAttribute('aria-expanded') === 'true') {
        closeMenu();
        menuButton.focus();
      }
    });
  }

  const revealItems = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.documentElement.classList.add('animations-ready');
    const observer = new IntersectionObserver((entries, instance) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          instance.unobserve(entry.target);
        }
      });
    }, { threshold: 0.14, rootMargin: '0px 0px -30px 0px' });
    revealItems.forEach((item) => observer.observe(item));
  } else {
    document.documentElement.classList.add('animations-ready');
    revealItems.forEach((item) => item.classList.add('is-visible'));
  }

  const mobileCta = document.querySelector('.mobile-cta');
  const hero = document.querySelector('.hero');
  if (mobileCta && hero && 'IntersectionObserver' in window) {
    const heroObserver = new IntersectionObserver(([entry]) => {
      mobileCta.classList.toggle('is-visible', !entry.isIntersecting);
    }, { threshold: 0.05 });
    heroObserver.observe(hero);
  } else if (mobileCta && hero) {
    const updateMobileCta = () => mobileCta.classList.toggle('is-visible', window.scrollY > hero.offsetHeight - window.innerHeight);
    updateMobileCta();
    window.addEventListener('scroll', updateMobileCta, { passive: true });
  }

  const quoteForm = document.querySelector('#quote-form');
  if (!quoteForm) return;
  const steps = [...quoteForm.querySelectorAll('[data-step]')];
  const nextButton = quoteForm.querySelector('[data-next]');
  const backButton = quoteForm.querySelector('[data-back]');
  const submitButton = quoteForm.querySelector('[data-submit]');
  const count = document.querySelector('[data-step-count]');
  const progress = document.querySelector('[data-progress]');
  const servicesError = quoteForm.querySelector('[data-services-error]');
  const formError = quoteForm.querySelector('[data-form-error]');
  let activeStep = 1;

  const currentStep = () => steps.find((step) => Number(step.dataset.step) === activeStep);
  const renderStep = () => {
    steps.forEach((step) => step.classList.toggle('is-active', Number(step.dataset.step) === activeStep));
    if (count) count.innerHTML = `0${activeStep} <span>/</span> 04`;
    if (progress) progress.style.width = `${(activeStep / steps.length) * 100}%`;
    if (backButton) backButton.hidden = activeStep === 1;
    if (nextButton) nextButton.hidden = activeStep === steps.length;
    if (submitButton) submitButton.hidden = activeStep !== steps.length;
    if (formError) formError.hidden = true;
  };

  const validateCurrent = () => {
    const step = currentStep();
    if (!step) return false;
    if (activeStep === 2) {
      const chosen = step.querySelectorAll('input[name="services[]"]:checked').length > 0;
      if (servicesError) servicesError.hidden = chosen;
      return chosen;
    }
    const fields = [...step.querySelectorAll('input[required], select[required], textarea[required]')];
    const invalid = fields.find((field) => !field.checkValidity());
    const stepError = step.querySelector('[data-step-error]');
    if (stepError) stepError.hidden = !invalid;
    if (invalid) {
      invalid.setAttribute('aria-invalid', 'true');
      if (invalid.name === 'postal_code' && invalid.validity.patternMismatch) {
        stepError.textContent = document.documentElement.lang === 'es'
          ? 'Escribe un código postal de 5 dígitos.'
          : 'Enter a 5-digit ZIP code.';
      } else if (invalid.name === 'postal_code') {
        stepError.textContent = document.documentElement.lang === 'es'
          ? 'Escribe un código postal de 5 dígitos.'
          : 'Enter a 5-digit ZIP code.';
      } else if (invalid.name === 'property_type') {
        stepError.textContent = document.documentElement.lang === 'es'
          ? 'Elige el tipo de propiedad.'
          : 'Choose a property type.';
      } else if (stepError) {
        stepError.textContent = document.documentElement.lang === 'es'
          ? 'Completa los datos obligatorios de la propiedad.'
          : 'Complete the required property details.';
      }
      invalid.focus();
      return false;
    }
    fields.forEach((field) => field.removeAttribute('aria-invalid'));
    return true;
  };

  nextButton?.addEventListener('click', () => {
    if (!validateCurrent()) return;
    activeStep = Math.min(steps.length, activeStep + 1);
    renderStep();
    document.querySelector('.quote-form-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
  backButton?.addEventListener('click', () => {
    activeStep = Math.max(1, activeStep - 1);
    renderStep();
  });
  quoteForm.addEventListener('submit', (event) => {
    if (!validateCurrent()) {
      event.preventDefault();
      return;
    }
    if (formError) formError.hidden = true;
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = document.documentElement.lang === 'es'
        ? 'Enviando… <span aria-hidden="true">→</span>'
        : 'Sending… <span aria-hidden="true">→</span>';
    }
  });
  renderStep();
})();

