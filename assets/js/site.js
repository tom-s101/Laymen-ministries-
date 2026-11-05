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

  document.querySelectorAll('[data-year]').forEach(function (el) {
    el.textContent = new Date().getFullYear();
  });
})();
