// Progressive enhancements of the salon websites. Every feature also works (in a simpler way) without JS.
(function () {
  var inBackend = window.parent !== window && !!window.parent.neos;

  // --- mobile navigation -----------------------------------------------------------------------
  var header = document.querySelector('[data-site-header]');
  var toggle = document.querySelector('[data-nav-toggle]');
  if (header && toggle) {
    var setOpen = function (open) {
      header.classList.toggle('nav-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    toggle.addEventListener('click', function () { setOpen(!header.classList.contains('nav-open')); });
    header.querySelectorAll('.site-nav a').forEach(function (link) {
      link.addEventListener('click', function () { setOpen(false); });
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') setOpen(false); });
  }

  // --- forms that need a confirmation (cancel an appointment, …) -------------------------------
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form.hasAttribute && form.hasAttribute('data-confirm') && !window.confirm(form.getAttribute('data-confirm'))) {
      event.preventDefault();
      event.stopImmediatePropagation();
    }
  }, true);

  // --- appointment form: inline validation and sending without page reload ---------------------
  document.querySelectorAll('[data-appointment-form]').forEach(function (form) {
    var closedDays = (form.getAttribute('data-closed-days') || '').split(',').filter(Boolean).map(Number);
    var field = function (name) { return form.elements.namedItem(name); };
    var setError = function (name, message) {
      var output = form.querySelector('[data-error-for="' + name + '"]');
      if (output) output.textContent = message || '';
      var input = field(name);
      var wrapper = input && input.closest ? input.closest('.field') : null;
      if (wrapper) wrapper.classList.toggle('has-error', !!message);
      if (input && input.setAttribute) input.setAttribute('aria-invalid', message ? 'true' : 'false');
    };
    var checkDate = function () {
      var date = field('date');
      if (!date || !date.value) { setError('date', ''); return true; }
      var closed = closedDays.indexOf(new Date(date.value + 'T12:00:00').getDay()) !== -1;
      setError('date', closed ? 'An diesem Tag ist der Salon geschlossen.' : '');
      return !closed;
    };
    if (field('date')) field('date').addEventListener('change', checkDate);

    form.addEventListener('submit', function (event) {
      if (!window.fetch || !window.FormData) return; // classic POST with redirect
      event.preventDefault();
      ['name', 'phone', 'email', 'date', 'privacy', 'form'].forEach(function (name) { setError(name, ''); });
      if (inBackend) {
        setError('form', 'Im Bearbeitungsmodus wird nichts gesendet – bitte auf der Website ausprobieren.');
        return;
      }
      var errors = {};
      if (field('name').value.trim().length < 2) errors.name = 'Bitte geben Sie Ihren Namen an.';
      if (!/^[0-9+()\/\s.-]{5,40}$/.test(field('phone').value.trim())) errors.phone = 'Bitte geben Sie eine gültige Telefonnummer an.';
      if (field('email').value && !field('email').checkValidity()) errors.email = 'Diese E-Mail-Adresse ist ungültig.';
      if (!checkDate()) errors.date = 'An diesem Tag ist der Salon geschlossen.';
      if (!field('privacy').checked) errors.privacy = 'Bitte stimmen Sie der Verarbeitung Ihrer Angaben zu.';
      var show = function (all) {
        var first = null;
        Object.keys(all).forEach(function (name) {
          setError(name, all[name]);
          if (!first && field(name) && field(name).focus) first = field(name);
        });
        if (first) first.focus();
      };
      if (Object.keys(errors).length) { show(errors); return; }

      form.classList.add('is-sending');
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          form.classList.remove('is-sending');
          if (data.ok) {
            form.reset();
            form.hidden = true;
            var error = document.getElementById('termin-fehler');
            if (error) error.classList.remove('is-visible');
            var success = document.getElementById('termin-gesendet');
            if (success) {
              success.classList.add('is-visible');
              success.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          } else {
            show(data.errors || { form: 'Bitte prüfen Sie Ihre Angaben.' });
          }
        })
        .catch(function () {
          form.classList.remove('is-sending');
          setError('form', 'Die Anfrage konnte nicht gesendet werden. Bitte versuchen Sie es erneut oder rufen Sie uns an.');
        });
    });
  });

  // --- before / after comparison -----------------------------------------------------------------
  document.querySelectorAll('[data-compare]').forEach(function (compare) {
    var range = compare.querySelector('.compare__range');
    var update = function () { compare.style.setProperty('--pos', range.value + '%'); };
    range.addEventListener('input', update);
    update();
  });

  // --- image slider --------------------------------------------------------------------------------
  document.querySelectorAll('[data-slider]').forEach(function (slider) {
    var track = slider.querySelector('[data-slider-track]');
    if (!track) return;
    var go = function (direction) { track.scrollBy({ left: direction * track.clientWidth * 0.8, behavior: 'smooth' }); };
    var prev = slider.querySelector('[data-slider-prev]');
    var next = slider.querySelector('[data-slider-next]');
    if (prev) prev.addEventListener('click', function () { go(-1); });
    if (next) next.addEventListener('click', function () { go(1); });
  });

  // --- Instagram posts: Instagram's embed is only loaded after the visitor clicked -------------
  var instagramScript = null;
  document.addEventListener('click', function (event) {
    var button = event.target.closest && event.target.closest('[data-instagram-load]');
    if (!button) return;
    var post = button.closest('[data-instagram-post]');
    var url = post && post.getAttribute('data-instagram-post');
    if (!url) return;
    var quote = document.createElement('blockquote');
    quote.className = 'instagram-media';
    quote.setAttribute('data-instgrm-permalink', url);
    quote.setAttribute('data-instgrm-version', '14');
    var link = document.createElement('a');
    link.href = url;
    link.textContent = 'Beitrag auf Instagram ansehen';
    quote.appendChild(link);
    post.innerHTML = '';
    post.appendChild(quote);
    post.classList.add('is-embedded');
    if (window.instgrm && window.instgrm.Embeds) {
      window.instgrm.Embeds.process();
    } else if (!instagramScript) {
      instagramScript = document.createElement('script');
      instagramScript.async = true;
      instagramScript.src = 'https://www.instagram.com/embed.js';
      document.body.appendChild(instagramScript);
    }
  });

  // --- gallery lightbox (not inside the Neos backend, where clicks select content) -------------
  if (inBackend) return;
  document.addEventListener('click', function (event) {
    var link = event.target.closest && event.target.closest('[data-lightbox]');
    if (!link) return;
    event.preventDefault();
    var overlay = document.createElement('div');
    overlay.className = 'lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-label', 'Bild vergrößert');
    var image = document.createElement('img');
    image.src = link.getAttribute('href');
    var thumb = link.querySelector('img');
    image.alt = thumb ? thumb.alt : '';
    overlay.appendChild(image);
    var close = function () { overlay.remove(); document.removeEventListener('keydown', onKey); };
    var onKey = function (e) { if (e.key === 'Escape') close(); };
    overlay.addEventListener('click', close);
    document.addEventListener('keydown', onKey);
    document.body.appendChild(overlay);
  });
})();
