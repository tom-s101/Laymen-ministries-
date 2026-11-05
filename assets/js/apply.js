(function () {
  var DRAFT_KEY = 'lmn-volunteer-draft-v1';

  var form = document.getElementById('apply-form');
  var steps = Array.prototype.slice.call(form.querySelectorAll('.form-step[data-step]'));
  var successStep = document.getElementById('success-step');
  var stepper = document.getElementById('stepper');
  var prevBtn = document.getElementById('prev-btn');
  var nextBtn = document.getElementById('next-btn');
  var nav = document.getElementById('form-nav');
  var alertBox = document.getElementById('form-alert');
  var saveState = document.getElementById('save-state');
  var current = 0;
  var furthest = 0;

  steps.forEach(function (step, i) {
    var li = document.createElement('li');
    var b = document.createElement('button');
    b.type = 'button';
    b.innerHTML = '<span class="bar"></span><span class="label">' + step.dataset.step + '</span>';
    b.addEventListener('click', function () { if (i <= furthest) go(i); });
    li.appendChild(b);
    stepper.appendChild(li);
  });

  function renderStepper() {
    Array.prototype.forEach.call(stepper.children, function (li, i) {
      li.classList.toggle('done', i < current);
      li.classList.toggle('current', i === current);
      li.querySelector('button').disabled = i > furthest;
      if (i === current) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
    });
  }

  function go(i, opts) {
    steps[current].classList.remove('active');
    current = i;
    furthest = Math.max(furthest, current);
    steps[current].classList.add('active');
    prevBtn.style.visibility = current === 0 ? 'hidden' : 'visible';
    nextBtn.textContent = current === steps.length - 1 ? 'Submit application' : 'Continue';
    if (current === steps.length - 1) {
      buildReview();
      document.getElementById('signed_date').value = new Date().toLocaleDateString();
    }
    hideAlert();
    renderStepper();
    updateSummary();
    saveDraft();
    if (!opts || !opts.noScroll) {
      window.scrollTo({ top: form.getBoundingClientRect().top + window.scrollY - 140, behavior: 'smooth' });
      var h = steps[current].querySelector('h2');
      h.setAttribute('tabindex', '-1');
      h.focus({ preventScroll: true });
    }
  }

  function valuesOf(name) {
    var els = form.querySelectorAll('[name="' + name + '"]');
    var out = [];
    Array.prototype.forEach.call(els, function (el) {
      if (el.type === 'radio' || el.type === 'checkbox' ? el.checked : el.value) out.push(el.value);
    });
    return out;
  }

  function applyConditions() {
    form.querySelectorAll('[data-show-if]').forEach(function (el) {
      var show = el.dataset.showIf.split('|').some(function (cond) {
        var parts = cond.split('=');
        return valuesOf(parts[0]).indexOf(parts.slice(1).join('=')) !== -1;
      });
      el.classList.toggle('hidden', !show);
    });
    var minor = isMinor();
    document.getElementById('guardian-block').classList.toggle('hidden', !minor);
    form.querySelectorAll('[data-guardian-required]').forEach(function (el) { el.required = minor; });
  }

  function ageOn(dobStr) {
    var dob = new Date(dobStr + 'T00:00:00');
    if (!dobStr || isNaN(dob)) return null;
    var d = new Date();
    var age = d.getFullYear() - dob.getFullYear();
    var m = d.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && d.getDate() < dob.getDate())) age--;
    return age;
  }

  function isMinor() {
    var a = ageOn(form.elements.dob.value);
    return a !== null && a < 18;
  }

  function isHidden(el) { return !!el.closest('.hidden'); }

  function setFieldError(field, msg) {
    var wrap = field.closest('.field');
    var em = wrap.querySelector('.error-msg');
    wrap.classList.toggle('invalid', !!msg);
    if (em) {
      em.dataset.default = em.dataset.default || em.textContent;
      em.textContent = typeof msg === 'string' && msg ? msg : em.dataset.default;
    }
    field.setAttribute('aria-invalid', msg ? 'true' : 'false');
  }

  function customError(el) {
    var name = el.name;
    if (name === 'dob' && el.value) {
      var age = ageOn(el.value);
      var track = valuesOf('track')[0];
      if (age === null || age < 0) return 'Enter a valid date of birth.';
      if (track === 'Student Missionary' && age < 17) return 'Student missionaries must be at least 17.';
      if (track === 'Long-Term Missionary' && age < 18) return 'Long-term missionaries must be 18 or older. Consider the Student program.';
    }
    if (name === 'start_date' && el.value) {
      var d = new Date(el.value + 'T00:00:00');
      var today = new Date(); today.setHours(0, 0, 0, 0);
      if (d < today) return 'Choose a date in the future.';
    }
    if (name === 'signature' && el.value) {
      var norm = function (s) { return s.toLowerCase().replace(/[^a-z]/g, ''); };
      var sig = norm(el.value);
      var first = norm(form.elements.first_name.value), last = norm(form.elements.last_name.value);
      if (sig.indexOf(first) === -1 || sig.indexOf(last) === -1) return 'Type your full name exactly as entered above.';
    }
    return '';
  }

  function validateField(el) {
    if (isHidden(el) || el.type === 'radio' || el.type === 'checkbox') return true;
    var msg = !el.checkValidity() || customError(el);
    setFieldError(el, msg);
    return !msg;
  }

  function validateStep(step) {
    var firstBad = null;
    step.querySelectorAll('input, select, textarea').forEach(function (el) {
      if (el.type === 'checkbox' && el.closest('.agree')) {
        var ok = isHidden(el) || !el.required || el.checked;
        el.closest('.agree').classList.toggle('invalid', !ok);
        if (!ok && !firstBad) firstBad = el;
        return;
      }
      if (!validateField(el) && !firstBad) firstBad = el;
    });
    step.querySelectorAll('.choice-group[data-required]').forEach(function (g) {
      if (isHidden(g)) return;
      var ok = valuesOf(g.dataset.required).length > 0;
      g.classList.toggle('invalid', !ok);
      if (!ok && !firstBad) firstBad = g.querySelector('input');
    });
    if (firstBad) {
      showAlert('Please fix the highlighted fields to continue.');
      firstBad.focus({ preventScroll: true });
      firstBad.scrollIntoView({ block: 'center', behavior: 'smooth' });
      return false;
    }
    return true;
  }

  function showAlert(msg) { alertBox.textContent = msg; alertBox.classList.add('show'); }
  function hideAlert() { alertBox.classList.remove('show'); }

  function labelFor(el) {
    if (el.type === 'radio' || el.type === 'checkbox') {
      var g = el.closest('.choice-group');
      if (g && g.dataset.label) return g.dataset.label;
      if (g && g.querySelector('.label')) return g.querySelector('.label').textContent.trim();
      return el.closest('fieldset').querySelector('legend').textContent.trim();
    }
    var clone = form.querySelector('label[for="' + el.id + '"]').cloneNode(true);
    clone.querySelectorAll('.optional').forEach(function (o) { o.remove(); });
    var group = el.closest('[data-prefix]');
    return (group ? group.dataset.prefix + ' — ' : '') + clone.textContent.trim();
  }

  function collectAll() {
    var data = {};
    Array.prototype.forEach.call(form.elements, function (el) {
      if (!el.name) return;
      if (el.type === 'checkbox' && !el.closest('.agree')) {
        data[el.name] = data[el.name] || [];
        if (el.checked) data[el.name].push(el.value);
      } else if (el.type === 'checkbox') {
        data[el.name] = el.checked;
      } else if (el.type === 'radio') {
        if (el.checked) data[el.name] = el.value;
      } else {
        data[el.name] = el.value;
      }
    });
    return data;
  }

  function collectSections() {
    return steps.slice(0, -1).map(function (step, i) {
      var seen = {};
      var fields = [];
      step.querySelectorAll('input, select, textarea').forEach(function (el) {
        if (!el.name || seen[el.name] || isHidden(el)) return;
        seen[el.name] = true;
        var v = valuesOf(el.name).join(', ');
        if (el.type === 'date' && v) v = new Date(v + 'T00:00:00').toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
        if (v) fields.push({ label: labelFor(el), value: v });
      });
      return { step: i, title: step.querySelector('h2').textContent, fields: fields };
    });
  }

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  }

  function buildReview() {
    var html = collectSections().map(function (sec) {
      var rows = sec.fields.map(function (f) { return '<dt>' + esc(f.label) + '</dt><dd>' + esc(f.value) + '</dd>'; }).join('');
      return '<section class="review-section"><header><h3>' + esc(sec.title) + '</h3>' +
        '<button type="button" data-edit="' + sec.step + '">Edit</button></header><dl>' + (rows || '<dd class="muted">Nothing entered</dd>') + '</dl></section>';
    }).join('');
    var review = document.getElementById('review');
    review.innerHTML = html;
    review.querySelectorAll('[data-edit]').forEach(function (b) {
      b.addEventListener('click', function () { go(parseInt(b.dataset.edit, 10)); });
    });
  }

  function updateSummary() {
    var d = collectAll();
    var set = function (id, v) { document.getElementById(id).textContent = v || '—'; };
    set('sum-track', d.track ? d.track.replace(' Missionary', '') : '');
    set('sum-start', d.start_date ? new Date(d.start_date + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', year: 'numeric' }) : '');
    set('sum-duration', d.duration);
    set('sum-name', [d.first_name, d.last_name].filter(Boolean).join(' '));
    var pct = successStep.classList.contains('active') ? 100 : Math.round((current / (steps.length)) * 100);
    document.getElementById('progress-bar').style.width = pct + '%';
  }

  var saveTimer;
  function saveDraft() {
    var d = collectAll();
    delete d.signature;
    localStorage.setItem(DRAFT_KEY, JSON.stringify({ data: d, step: current, furthest: furthest }));
    saveState.textContent = 'Saved on this device';
  }

  function restoreDraft() {
    var raw = localStorage.getItem(DRAFT_KEY);
    if (!raw) return false;
    var draft = JSON.parse(raw);
    var d = draft.data;
    Object.keys(d).forEach(function (name) {
      var els = form.querySelectorAll('[name="' + name + '"]');
      Array.prototype.forEach.call(els, function (el) {
        if (el.type === 'checkbox' && !el.closest('.agree')) el.checked = (d[name] || []).indexOf(el.value) !== -1;
        else if (el.type === 'checkbox') el.checked = !!d[name];
        else if (el.type === 'radio') el.checked = el.value === d[name];
        else el.value = d[name];
      });
    });
    furthest = draft.furthest;
    return draft.step;
  }

  form.addEventListener('input', function (e) {
    var el = e.target;
    if (el.closest('.field') && el.closest('.field').classList.contains('invalid')) validateField(el);
    var g = el.closest('.choice-group.invalid');
    if (g && valuesOf(g.dataset.required).length) g.classList.remove('invalid');
    var a = el.closest('.agree.invalid');
    if (a && el.checked) a.classList.remove('invalid');
    applyConditions();
    updateSummary();
    clearTimeout(saveTimer);
    saveState.textContent = 'Saving…';
    saveTimer = setTimeout(saveDraft, 400);
  });
  form.addEventListener('change', function (e) {
    applyConditions();
    if (e.target.name === 'track' && form.elements.dob.value) validateField(form.elements.dob);
  });
  form.addEventListener('focusout', function (e) {
    var el = e.target;
    if (el.matches('input, select, textarea') && el.value && el.type !== 'radio' && el.type !== 'checkbox') validateField(el);
  });
  form.addEventListener('submit', function (e) { e.preventDefault(); nextBtn.click(); });

  prevBtn.addEventListener('click', function () { go(current - 1); });
  nextBtn.addEventListener('click', function () {
    if (!validateStep(steps[current])) return;
    if (current < steps.length - 1) go(current + 1);
    else submit();
  });

  document.getElementById('clear-draft').addEventListener('click', function () {
    if (!confirm('Clear everything you have entered on this device?')) return;
    localStorage.removeItem(DRAFT_KEY);
    window.location.href = window.location.pathname;
  });

  function submit() {
    hideAlert();
    nextBtn.disabled = true;
    nextBtn.textContent = 'Submitting…';
    fetch('api/submit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ data: collectAll(), sections: collectSections() })
    }).then(function (res) { return res.json(); }).then(function (body) {
      if (!body.ok) throw new Error(body.error);
      localStorage.removeItem(DRAFT_KEY);
      document.getElementById('ref-id').textContent = body.id;
      steps[current].classList.remove('active');
      successStep.classList.add('active');
      nav.classList.add('hidden');
      stepper.querySelectorAll('li').forEach(function (li) { li.classList.add('done'); li.classList.remove('current'); });
      updateSummary();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }).catch(function (err) {
      showAlert('We couldn\'t submit your application (' + err.message + '). Your answers are still saved on this device. Please try again, or email office@lmn.org.');
      nextBtn.disabled = false;
      nextBtn.textContent = 'Submit application';
    });
  }

  var startAt = restoreDraft();
  var params = new URLSearchParams(location.search);
  var preset = { student: 'Student Missionary', longterm: 'Long-Term Missionary' }[params.get('track')];
  if (preset && !valuesOf('track').length) {
    form.querySelector('input[name="track"][value="' + preset + '"]').checked = true;
  }
  var today = new Date();
  form.elements.start_date.min = today.toISOString().slice(0, 10);
  form.elements.dob.max = today.toISOString().slice(0, 10);
  applyConditions();
  go(startAt || 0, { noScroll: true });
  if (startAt !== false) saveState.textContent = 'Welcome back — your draft was restored';
})();
