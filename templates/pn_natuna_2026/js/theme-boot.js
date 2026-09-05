(function () {
  try {
    var dark = localStorage.getItem('pnNatunaDark') === '1';
    document.body.classList.toggle('is-dark', dark);
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    var theme = document.getElementById('theme-color-meta');
    if (theme) theme.content = dark ? '#151015' : '#8f1f0b';
  } catch (e) { /* private mode */ }
})();
