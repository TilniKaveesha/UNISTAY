/* UniStay Admin enhancement: theme, mobile menu, native modal & action confirmations. */
(() => {
  'use strict';
  const body = document.body;
  const menuButton = document.getElementById('ua-menu-toggle');
  const scrim = document.getElementById('ua-scrim');
  const themeButton = document.getElementById('ua-theme-toggle');

  const closeMenu = () => {
    body.classList.remove('ua-menu-open');
    menuButton?.setAttribute('aria-expanded', 'false');
    if (scrim) scrim.hidden = true;
  };
  menuButton?.addEventListener('click', () => {
    const isOpen = !body.classList.contains('ua-menu-open');
    body.classList.toggle('ua-menu-open', isOpen);
    menuButton.setAttribute('aria-expanded', String(isOpen));
    if (scrim) scrim.hidden = !isOpen;
  });
  scrim?.addEventListener('click', closeMenu);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeMenu();
  });

  try {
    const savedTheme = localStorage.getItem('unistay-admin-theme');
    if (savedTheme === 'dark' || savedTheme === 'light') body.dataset.uaTheme = savedTheme;
  } catch (_) { /* Private browsing may block storage. */ }
  themeButton?.addEventListener('click', () => {
    const next = body.dataset.uaTheme === 'dark' ? 'light' : 'dark';
    body.dataset.uaTheme = next;
    try { localStorage.setItem('unistay-admin-theme', next); } catch (_) {}
  });

  // Progressive enhancement: without JS, forms still submit and server protects writes.
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || 'Confirm this change?')) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('[data-open-dialog]').forEach((button) => {
    const dialog = document.getElementById(button.dataset.openDialog);
    if (!(dialog instanceof HTMLDialogElement)) return;
    button.addEventListener('click', () => {
      dialog.showModal();
      const firstInput = dialog.querySelector('input:not([type=hidden])');
      firstInput?.focus();
    });
  });
  document.querySelectorAll('[data-close-dialog]').forEach((button) => {
    button.addEventListener('click', () => button.closest('dialog')?.close());
  });
  document.querySelectorAll('dialog.ua-dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) dialog.close();
    });
  });
})();
