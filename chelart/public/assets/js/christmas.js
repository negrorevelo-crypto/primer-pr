(() => {
  const form = document.querySelector('#christmas-form');
  if (!form) return;

  const MAX_PHOTOS = 6;
  const MAX_BYTES = 8 * 1024 * 1024;
  const RESIZE_SIDE = 2000;
  const TYPES = ['image/jpeg', 'image/png', 'image/webp'];
  const es = () => document.documentElement.lang === 'es';
  const t = (en, sp) => (es() ? sp : en);

  const messages = {
    full_name: ['Enter your full name.', 'Escribe tu nombre completo.'],
    email: ['Enter a valid email address, e.g. name@example.com.', 'Escribe un correo válido, p. ej. nombre@ejemplo.com.'],
    phone: ['Enter a valid phone number with area code, e.g. (407) 555-0123.', 'Escribe un teléfono válido con código de área, p. ej. (407) 555-0123.'],
    contact_method: ['Choose how you’d like us to contact you.', 'Elige cómo prefieres que te contactemos.'],
    property_address: ['Enter the property address.', 'Escribe la dirección de la propiedad.'],
    city: ['Enter the city.', 'Escribe la ciudad.'],
    zip_code: ['Enter a 5-digit ZIP code.', 'Escribe un código postal de 5 dígitos.'],
    property_type: ['Choose the property type.', 'Elige el tipo de propiedad.'],
    package_type: ['Choose a package.', 'Elige un paquete.'],
    'decoration_areas[]': ['Choose at least one area to decorate.', 'Elige al menos un área para decorar.'],
    decoration_supplier: ['Tell us who supplies the decorations.', 'Indica quién aporta la decoración.'],
    property_size: ['Choose the approximate property size.', 'Elige el tamaño aproximado de la propiedad.'],
    installation_date: ['Choose an installation date from today onward.', 'Elige una fecha de instalación desde hoy en adelante.'],
    removal_date: ['The removal date must be after the installation date.', 'La fecha de retiro debe ser posterior a la de instalación.'],
    privacy_consent: ['Accept the privacy notice and contact authorization to continue.', 'Acepta el aviso de privacidad y la autorización de contacto para continuar.'],
  };
  const message = (key) => t(...messages[key]);

  const errorBox = form.querySelector('[data-form-error]');
  const submitButton = form.querySelector('[data-submit]');
  const languageField = form.querySelector('[data-language-field]');
  const field = (name) => form.elements.namedItem(name);

  /* Paquete preseleccionado desde las tarjetas, sin recargar la página. */
  const selectPackage = (key) => {
    const radio = form.querySelector(`input[name="package_type"][value="${CSS.escape(key)}"]`);
    if (radio) radio.checked = true;
  };
  document.querySelectorAll('[data-package-link]').forEach((link) => link.addEventListener('click', (event) => {
    event.preventDefault();
    const key = link.dataset.packageLink;
    selectPackage(key);
    const url = new URL(window.location.href);
    url.searchParams.set('package', key);
    url.hash = 'christmas-quote';
    history.replaceState(null, '', url);
    document.querySelector('#christmas-quote')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }));

  /* Enlaces internos (#christmas-quote, #packages) con desplazamiento suave en la misma página. */
  document.querySelectorAll('a[href*="christmas-decoration-services/#"]').forEach((link) => link.addEventListener('click', (event) => {
    const target = document.getElementById(link.getAttribute('href').split('#')[1]);
    if (!target) return;
    event.preventDefault();
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${target.id}`);
  }));

  /* La fecha de retiro no puede ser anterior a la de instalación. */
  const install = field('installation_date');
  const removal = field('removal_date');
  install?.addEventListener('change', () => {
    if (!removal) return;
    if (install.value) {
      const next = new Date(`${install.value}T12:00:00`);
      next.setDate(next.getDate() + 1);
      removal.min = next.toISOString().slice(0, 10);
    } else {
      removal.min = install.min;
    }
  });

  /* Fotos: se validan y se reducen en el navegador (máx. 2000 px) para subir rápido desde el móvil
     y quitar metadatos como la ubicación GPS. El servidor vuelve a validar todo. */
  const photoInput = form.querySelector('[data-photo-input]');
  const photoList = form.querySelector('[data-photo-list]');
  const canSetFiles = typeof DataTransfer === 'function' && (() => { try { return Boolean(new DataTransfer().items); } catch { return false; } })();
  let photos = [];
  let pending = 0;

  const status = (text) => {
    let note = form.querySelector('.xmas-photo-status');
    if (!note) {
      note = document.createElement('p');
      note.className = 'xmas-photo-status';
      photoList.after(note);
    }
    note.textContent = text;
    note.hidden = !text;
  };

  const shrink = async (file) => {
    if (!window.createImageBitmap) return file;
    try {
      const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
      const scale = Math.min(1, RESIZE_SIDE / Math.max(bitmap.width, bitmap.height));
      if (scale === 1 && file.size < 1.5 * 1024 * 1024) { bitmap.close?.(); return file; }
      const canvas = document.createElement('canvas');
      canvas.width = Math.round(bitmap.width * scale);
      canvas.height = Math.round(bitmap.height * scale);
      canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
      bitmap.close?.();
      const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
      if (!blob || blob.size >= file.size) return file;
      return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() });
    } catch {
      return file;
    }
  };

  const syncInput = () => {
    if (!canSetFiles) return;
    const transfer = new DataTransfer();
    photos.forEach((photo) => transfer.items.add(photo.file));
    photoInput.files = transfer.files;
  };

  const render = () => {
    photoList.querySelectorAll('img').forEach((img) => URL.revokeObjectURL(img.src));
    photoList.replaceChildren(...photos.map((photo, index) => {
      const item = document.createElement('li');
      const img = document.createElement('img');
      img.src = URL.createObjectURL(photo.file);
      img.alt = photo.file.name;
      item.append(img);
      if (canSetFiles) {
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.textContent = '×';
        remove.setAttribute('aria-label', `${t('Remove photo', 'Quitar foto')} ${index + 1}`);
        remove.addEventListener('click', () => { photos.splice(index, 1); syncInput(); render(); status(''); });
        item.append(remove);
      }
      return item;
    }));
  };

  photoInput?.addEventListener('change', async () => {
    const chosen = [...photoInput.files];
    // Sin DataTransfer el navegador reemplaza la selección; con él se acumulan las fotos elegidas.
    if (!canSetFiles) photos = [];
    const problems = [];
    pending += 1;
    status(t('Preparing photos…', 'Preparando fotos…'));
    for (const file of chosen) {
      if (photos.length >= MAX_PHOTOS) { problems.push(t('You can attach up to 6 photos.', 'Puedes adjuntar hasta 6 fotos.')); break; }
      if (!TYPES.includes(file.type)) { problems.push(`${file.name}: ${t('use JPG, PNG or WebP.', 'usa JPG, PNG o WebP.')}`); continue; }
      const ready = canSetFiles ? await shrink(file) : file;
      if (ready.size > MAX_BYTES) { problems.push(`${file.name}: ${t('larger than 8 MB.', 'pesa más de 8 MB.')}`); continue; }
      photos.push({ file: ready });
    }
    pending -= 1;
    if (canSetFiles) syncInput();
    else if (problems.length) { photoInput.value = ''; photos = []; }
    render();
    status(problems.join(' '));
  });

  /* Validación antes de enviar (el servidor repite todas las reglas). */
  const groupOf = (input) => input.closest('.xmas-group');
  const validate = () => {
    const errors = [];
    const flag = (key, element) => {
      errors.push({ key, element });
      if (element?.matches?.('input:not([type=radio]):not([type=checkbox]), select, textarea, input[name=privacy_consent]')) element.setAttribute('aria-invalid', 'true');
      else groupOf(element)?.classList.add('is-invalid');
    };
    form.querySelectorAll('[aria-invalid]').forEach((element) => element.removeAttribute('aria-invalid'));
    form.querySelectorAll('.xmas-group.is-invalid').forEach((group) => group.classList.remove('is-invalid'));

    const value = (name) => (field(name)?.value || '').trim();
    if (value('full_name').length < 2) flag('full_name', field('full_name'));
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value('email'))) flag('email', field('email'));
    const phone = value('phone');
    const digits = phone.replace(/\D/g, '');
    const phoneOk = /^\+?[0-9\s().-]+$/.test(phone)
      && (digits.length === 10 || (digits.length === 11 && digits[0] === '1') || (phone.startsWith('+') && digits.length >= 8 && digits.length <= 15));
    if (!phoneOk) flag('phone', field('phone'));
    const radio = (name) => form.querySelector(`input[name="${name}"]:checked`);
    if (!radio('contact_method')) flag('contact_method', form.querySelector('input[name="contact_method"]'));
    if (value('property_address').length < 5) flag('property_address', field('property_address'));
    if (!value('city')) flag('city', field('city'));
    if (!/^\d{5}(-\d{4})?$/.test(value('zip_code'))) flag('zip_code', field('zip_code'));
    if (!value('property_type')) flag('property_type', field('property_type'));
    if (!radio('package_type')) flag('package_type', form.querySelector('input[name="package_type"]'));
    if (!form.querySelector('input[name="decoration_areas[]"]:checked')) flag('decoration_areas[]', form.querySelector('input[name="decoration_areas[]"]'));
    if (!radio('decoration_supplier')) flag('decoration_supplier', form.querySelector('input[name="decoration_supplier"]'));
    if (!radio('property_size')) flag('property_size', form.querySelector('input[name="property_size"]'));
    if (install?.value && (install.value < install.min || install.value > install.max)) flag('installation_date', install);
    if (removal?.value && (removal.value < removal.min || removal.value > removal.max || (install?.value && removal.value <= install.value))) flag('removal_date', removal);
    const consent = field('privacy_consent');
    if (!consent?.checked) flag('privacy_consent', consent);
    return errors;
  };

  const showErrors = (errors) => {
    const list = document.createElement('ul');
    errors.forEach(({ key }) => {
      const item = document.createElement('li');
      item.textContent = message(key);
      list.append(item);
    });
    const title = document.createElement('strong');
    title.textContent = t('Please review the highlighted fields:', 'Revisa los campos marcados:');
    errorBox.replaceChildren(title, list);
    errorBox.hidden = false;
    const first = errors[0].element;
    first?.focus({ preventScroll: true });
    first?.closest('.xmas-fieldset')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  // Al corregir un campo se quita su marca.
  form.addEventListener('input', (event) => event.target.removeAttribute?.('aria-invalid'));
  form.addEventListener('change', (event) => { groupOf(event.target)?.classList.remove('is-invalid'); event.target.removeAttribute?.('aria-invalid'); });
  // Cambiar de idioma limpia los mensajes generados en el idioma anterior.
  document.querySelectorAll('[data-language-switch]').forEach((button) => button.addEventListener('click', () => {
    errorBox.hidden = true;
    status('');
  }));

  form.addEventListener('submit', (event) => {
    if (languageField) languageField.value = es() ? 'es' : 'en';
    if (pending > 0) {
      event.preventDefault();
      status(t('Photos are still being prepared. Try again in a moment.', 'Las fotos aún se están preparando. Inténtalo en un momento.'));
      return;
    }
    const errors = validate();
    if (errors.length) {
      event.preventDefault();
      showErrors(errors);
      return;
    }
    errorBox.hidden = true;
    submitButton.disabled = true;
    submitButton.innerHTML = t('Sending… <span aria-hidden="true">→</span>', 'Enviando… <span aria-hidden="true">→</span>');
  });

  // Si el usuario vuelve con el botón Atrás, el botón queda habilitado otra vez.
  window.addEventListener('pageshow', () => {
    submitButton.disabled = false;
    submitButton.innerHTML = t('Submit my Christmas quote request <span aria-hidden="true">→</span>', 'Enviar mi solicitud de cotización navideña <span aria-hidden="true">→</span>');
  });
})();
