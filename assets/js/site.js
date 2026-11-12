(function () {
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('site-nav');
  toggle.addEventListener('click', function () {
    toggle.setAttribute('aria-expanded', nav.classList.toggle('open'));
  });

  var subs = Array.prototype.slice.call(document.querySelectorAll('.menu .has-sub'));
  function closeAll(except) {
    subs.forEach(function (li) {
      if (li === except) return;
      li.classList.remove('open');
      li.querySelector('button').setAttribute('aria-expanded', 'false');
    });
  }
  subs.forEach(function (li) {
    var btn = li.querySelector('button');
    btn.addEventListener('click', function () {
      closeAll(li);
      btn.setAttribute('aria-expanded', li.classList.toggle('open'));
    });
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.menu .has-sub')) closeAll();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAll();
  });

  document.querySelectorAll('.more-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var more = document.getElementById(btn.getAttribute('aria-controls'));
      more.hidden = !more.hidden;
      btn.textContent = more.hidden ? 'Read more' : 'Show less';
      btn.setAttribute('aria-expanded', !more.hidden);
      if (more.hidden) btn.closest('section').scrollIntoView();
    });
  });

  var box = document.getElementById('lightbox');
  if (box) {
    document.querySelectorAll('.gallery a').forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var alt = a.querySelector('img').alt;
        box.querySelector('img').src = a.href;
        box.querySelector('img').alt = alt;
        box.querySelector('p').textContent = alt;
        box.showModal();
      });
    });
    box.querySelector('.lightbox-close').addEventListener('click', function () { box.close(); });
    box.addEventListener('click', function (e) { if (e.target === box) box.close(); });
  }

  document.querySelectorAll('[data-year]').forEach(function (el) {
    el.textContent = new Date().getFullYear();
  });
})();
