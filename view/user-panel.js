(() => {
  const app = document.querySelector('.app[data-view="dashboard"]');
  if (!app) return;

  app.classList.add('is-enhanced');
  app.classList.add('has-js-icons');

  const icons = {
    activity: '<path d="M22 12h-4l-3 8-6-16-3 8H2" />',
    bar: '<path d="M4 20V10" /><path d="M12 20V4" /><path d="M20 20v-6" />',
    bookmark: '<path d="M6 3h12v18l-6-4-6 4V3z" />',
    bowl: '<path d="M4 11h16a8 8 0 0 1-16 0z" /><path d="M8 11V8" /><path d="M12 11V6" /><path d="M16 11V8" />',
    cart: '<circle cx="9" cy="20" r="1.5" /><circle cx="18" cy="20" r="1.5" /><path d="M2 3h3l3 12h10l3-8H7" />',
    chef: '<path d="M7 10a4 4 0 1 1 7.5-2A3.5 3.5 0 1 1 17 14H7a4 4 0 0 1 0-8" /><path d="M7 14v5h10v-5" />',
    check: '<path d="M20 6 9 17l-5-5" />',
    dollar: '<path d="M12 2v20" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6" />',
    dumbbell: '<path d="M6 6v12" /><path d="M18 6v12" /><path d="M3 9v6" /><path d="M21 9v6" /><path d="M6 12h12" />',
    grid: '<path d="M4 4h6v6H4z" /><path d="M14 4h6v6h-6z" /><path d="M4 14h6v6H4z" /><path d="M14 14h6v6h-6z" />',
    headset: '<path d="M4 13a8 8 0 0 1 16 0" /><path d="M4 13v3a2 2 0 0 0 2 2h1v-7H6a2 2 0 0 0-2 2z" /><path d="M20 13v3a2 2 0 0 1-2 2h-1v-7h1a2 2 0 0 1 2 2z" /><path d="M13 20h2a3 3 0 0 0 3-3" />',
    heart: '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z" />',
    help: '<circle cx="12" cy="12" r="9" /><path d="M9.5 9a2.7 2.7 0 0 1 5 1.4c0 2-2.5 2.1-2.5 4" /><path d="M12 18h.01" />',
    list: '<path d="M8 6h13" /><path d="M8 12h13" /><path d="M8 18h13" /><path d="M3 6h.01" /><path d="M3 12h.01" /><path d="M3 18h.01" />',
    mail: '<path d="M4 6h16v12H4z" /><path d="m4 7 8 6 8-6" />',
    plus: '<path d="M12 5v14" /><path d="M5 12h14" />',
    ruler: '<path d="M3 17 17 3l4 4L7 21z" /><path d="m14 6 4 4" /><path d="m11 9 2 2" /><path d="m8 12 2 2" />',
    shield: '<path d="M12 3 20 7v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z" />',
    store: '<path d="M4 10h16l-1-6H5l-1 6z" /><path d="M6 10v10h12V10" /><path d="M9 20v-6h6v6" />',
    target: '<circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="5" /><circle cx="12" cy="12" r="1" />',
    user: '<circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" />'
  };

  function iconSvg(name) {
    const paths = icons[name] || icons.activity;
    return `<span class="widget-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false">${paths}</svg></span>`;
  }

  function addIcon(target, iconName) {
    if (!target || target.querySelector(':scope > .widget-icon')) return;
    target.classList.add('has-widget-icon');
    target.insertAdjacentHTML('afterbegin', iconSvg(iconName));
  }

  function normalizedText(node) {
    return (node ? node.textContent : '').trim().toLowerCase();
  }

  const labelIconMap = [
    ['saved meals', 'bookmark'],
    ['ingredients', 'bowl'],
    ['avg protein', 'bar'],
    ['total minutes', 'activity'],
    ['sessions', 'dumbbell'],
    ['objectives', 'target'],
    ['products', 'store'],
    ['categories', 'grid'],
    ['cart', 'cart'],
    ['profile ready', 'check'],
    ['bmi', 'ruler'],
    ['budget', 'dollar'],
    ['total requests', 'headset'],
    ['resolved', 'check'],
    ['pending', 'help']
  ];

  document.querySelectorAll('.insight-card, .support-counter').forEach((card) => {
    const label = normalizedText(card.querySelector('span'));
    const match = labelIconMap.find(([needle]) => label.includes(needle));
    addIcon(card, match ? match[1] : 'activity');
  });

  const headingIconMap = [
    ['add custom meal', 'plus'],
    ['ai meal generator', 'chef'],
    ['my meals', 'list'],
    ['available products', 'store'],
    ['shopping cart', 'cart'],
    ['customer reviews', 'heart'],
    ['add an exercise', 'dumbbell'],
    ['my exercises', 'activity'],
    ['add objective', 'target'],
    ['my objectives', 'target'],
    ['physical information', 'ruler'],
    ['goals', 'target'],
    ['health information', 'heart'],
    ['submit a support request', 'headset'],
    ['my requests', 'mail'],
    ['frequently asked questions', 'help']
  ];

  document.querySelectorAll('.card h3').forEach((heading) => {
    const text = normalizedText(heading);
    const match = headingIconMap.find(([needle]) => text.includes(needle));
    addIcon(heading, match ? match[1] : 'shield');
  });

  document.querySelectorAll('.meal-coach-kicker').forEach((coachKicker) => addIcon(coachKicker, 'chef'));
  document.querySelectorAll('.support-stat-copy').forEach((supportStat) => addIcon(supportStat, 'headset'));

  const activeNavLink = app.querySelector('.sidebar .nav-link.active');
  if (activeNavLink) {
    activeNavLink.scrollIntoView({ block: 'nearest', inline: 'center' });
  }

  const navTransitionTargets = new Set(['meals.php', 'exercises.php', 'store.php', 'profile.php', 'support.php']);
  let isNavigating = false;

  function pageNameFromUrl(url) {
    const path = new URL(url, window.location.href).pathname;
    return path.substring(path.lastIndexOf('/') + 1) || 'dashboard.php';
  }

  window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    isNavigating = false;
    document.body.classList.remove('is-page-navigating');
    document.querySelectorAll('.is-nav-target').forEach((target) => target.classList.remove('is-nav-target'));
  });

  const revealTargets = app.querySelectorAll(
    '.template-hero, .page-head, .wellness-hero, .stat, .insight-card, .card, .product-card, .reclamation, .exercise-smart-card, .support-footer'
  );

  revealTargets.forEach((target, index) => {
    target.classList.add('reveal-item');
    target.style.setProperty('--reveal-delay', `${Math.min(index * 35, 320)}ms`);
  });

  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.08 });

    revealTargets.forEach((target) => observer.observe(target));
  } else {
    revealTargets.forEach((target) => target.classList.add('is-visible'));
  }

  document.addEventListener('pointerdown', (event) => {
    const target = event.target.closest('.btn, .icon-btn, .filter-chip, .star-button, .nav-link');
    if (!target || target.disabled) return;
    target.classList.add('is-pressed');
    window.setTimeout(() => target.classList.remove('is-pressed'), 180);
  });

  document.addEventListener('click', (event) => {
    const link = event.target.closest('.sidebar .nav-link[href]');
    if (!link || link.classList.contains('active') || link.classList.contains('portal-link')) return;
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target && link.target !== '_self') return;

    const destination = new URL(link.href, window.location.href);
    if (destination.origin !== window.location.origin) return;
    if (!navTransitionTargets.has(pageNameFromUrl(destination.href))) return;
    if (isNavigating || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    event.preventDefault();
    isNavigating = true;
    link.classList.add('is-nav-target');
    document.body.classList.add('is-page-navigating');
    window.setTimeout(() => {
      window.location.href = link.href;
    }, 80);
  });

  const fieldLabels = Array.from(app.querySelectorAll('form label')).filter((label) => (
    !label.classList.contains('store-search') && label.querySelector('input, textarea, select')
  ));

  function syncFieldLabel(label) {
    const field = label.querySelector('input, textarea, select');
    if (!field) return;
    label.classList.toggle('has-value', String(field.value || '').trim() !== '');
  }

  fieldLabels.forEach((label) => {
    const field = label.querySelector('input, textarea, select');
    syncFieldLabel(label);
    field.addEventListener('input', () => syncFieldLabel(label));
    field.addEventListener('change', () => syncFieldLabel(label));
  });

  const innerPageNav = !app.matches('[data-page="home"]') ? app.querySelector('.sidebar') : null;
  let navScrollTimer = null;
  if (innerPageNav) {
    window.addEventListener('scroll', () => {
      app.classList.add('nav-is-moving');
      window.clearTimeout(navScrollTimer);
      navScrollTimer = window.setTimeout(() => {
        app.classList.remove('nav-is-moving');
      }, 220);
    }, { passive: true });
  }

  let toastTimer = null;
  function showToast(message) {
    let toast = document.querySelector('.user-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'user-toast';
      toast.setAttribute('role', 'status');
      document.body.appendChild(toast);
    }

    toast.textContent = message;
    toast.classList.add('is-visible');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 1800);
  }

  document.addEventListener('click', (event) => {
    const addButton = event.target.closest('.add-cart-btn');
    if (addButton && !addButton.disabled) {
      const card = addButton.closest('.product-card');
      showToast(`${card ? card.dataset.name : 'Product'} added to cart`);
    }
  });
})();
