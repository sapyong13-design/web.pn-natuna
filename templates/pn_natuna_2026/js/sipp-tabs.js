(() => {
  const tabs = Array.from(document.querySelectorAll('.sipp-day-tabs [role="tab"]'));
  const activate = (tab) => tabs.forEach((item) => {
    const active = item === tab;
    item.setAttribute('aria-selected', active ? 'true' : 'false');
    item.tabIndex = active ? 0 : -1;
    document.getElementById(item.getAttribute('aria-controls')).hidden = !active;
  });
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activate(tab));
    tab.addEventListener('keydown', (event) => {
      if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
      event.preventDefault();
      const offset = event.key === 'ArrowRight' ? 1 : tabs.length - 1;
      const next = tabs[(index + offset) % tabs.length];
      activate(next);
      next.focus();
    });
  });
})();
