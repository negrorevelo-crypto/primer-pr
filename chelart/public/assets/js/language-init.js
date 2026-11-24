(() => {
  const supported = new Set(['en', 'es']);
  const params = new URLSearchParams(window.location.search);
  let explicit = params.get('lang');
  let saved = '';
  try {
    saved = window.localStorage.getItem('chelart-language') || '';
    if (supported.has(explicit)) window.localStorage.setItem('chelart-language', explicit);
  } catch {}

  const preferred = (navigator.languages && navigator.languages[0]) || navigator.language || 'en';
  const automatic = /^es(?:-|$)/i.test(preferred) ? 'es' : 'en';
  const language = supported.has(explicit) ? explicit : (supported.has(saved) ? saved : automatic);
  document.documentElement.lang = language;
  document.documentElement.dataset.language = language;
})();
