document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.menu-toggle');
  const menu = document.querySelector('.main-menu-list');
  const close = document.querySelector('.menu-close');
  const backdrop = document.querySelector('.menu-backdrop');
  const mobileQuery = window.matchMedia('(max-width: 760px)');

  if (!toggle || !menu) {
    setupAccessibilityTools();
    return;
  }

  let trigger = toggle;
  let lockedScrollY = 0;
  const focusableSelector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';
  const parents = Array.from(menu.querySelectorAll('li.parent, li.deeper')).filter((item) => {
    const child = Array.from(item.children).find((element) => element.tagName === 'UL');
    return Boolean(child);
  });

  parents.forEach((item, index) => {
    const child = Array.from(item.children).find((element) => element.tagName === 'UL');
    const link = Array.from(item.children).find((element) => element.matches('a, span'));
    if (!child || !link || item.querySelector(':scope > .submenu-toggle')) return;
    child.id ||= `mobile-submenu-${index + 1}`;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'submenu-toggle';
    button.setAttribute('aria-controls', child.id);
    const currentBranch = item.matches('.active, .current') || Boolean(item.querySelector('.active, .current, [aria-current="page"]'));
    button.setAttribute('aria-label', `${currentBranch ? 'Tutup' : 'Buka'} submenu ${link.textContent.trim()}`);
    button.setAttribute('aria-expanded', String(currentBranch));
    child.hidden = mobileQuery.matches && !currentBranch;
    item.classList.toggle('submenu-open', currentBranch);
    link.insertAdjacentElement('afterend', button);
    button.addEventListener('click', () => {
      const opening = button.getAttribute('aria-expanded') !== 'true';
      if (opening) {
        const level = item.parentElement;
        Array.from(level.children).forEach((sibling) => {
          if (sibling === item) return;
          const siblingButton = sibling.querySelector(':scope > .submenu-toggle');
          const siblingList = siblingButton && sibling.querySelector(':scope > ul');
          if (siblingButton && siblingList) {
            siblingButton.setAttribute('aria-expanded', 'false');
            siblingButton.setAttribute('aria-label', siblingButton.getAttribute('aria-label').replace(/^Tutup/, 'Buka'));
            siblingList.hidden = true;
            sibling.classList.remove('submenu-open');
          }
        });
      }
      button.setAttribute('aria-expanded', String(opening));
      button.setAttribute('aria-label', button.getAttribute('aria-label').replace(opening ? /^Buka/ : /^Tutup/, opening ? 'Tutup' : 'Buka'));
      child.hidden = !opening;
      item.classList.toggle('submenu-open', opening);
    });
  });

  menu.inert = mobileQuery.matches;
  let scrollLocked = false;
  const setMenuOpen = (open, options = {}) => {
    open = Boolean(open && mobileQuery.matches);
    const wasOpen = menu.classList.contains('is-open');
    toggle.setAttribute('aria-expanded', String(open));
    menu.classList.toggle('is-open', open);
    menu.inert = mobileQuery.matches && !open;
    document.body.classList.toggle('menu-drawer-open', open);
    backdrop.hidden = !open;
    if (open) {
      trigger = options.trigger || document.activeElement || toggle;
      lockedScrollY = window.scrollY;
      scrollLocked = true;
      document.body.style.position = 'fixed';
      document.body.style.top = `-${lockedScrollY}px`;
      document.body.style.width = '100%';
      menu.setAttribute('role', 'dialog');
      menu.setAttribute('aria-modal', 'true');
      menu.setAttribute('aria-labelledby', 'mobile-menu-title');
      close?.focus();
    } else {
      menu.removeAttribute('role');
      menu.removeAttribute('aria-modal');
      menu.removeAttribute('aria-labelledby');
      document.body.style.position = '';
      document.body.style.top = '';
      document.body.style.width = '';
      if (scrollLocked && options.restoreScroll !== false) window.scrollTo(0, lockedScrollY);
      scrollLocked = false;
      if (wasOpen && options.restoreFocus !== false && trigger instanceof HTMLElement) trigger.focus();
    }
  };

  toggle.addEventListener('click', () => setMenuOpen(toggle.getAttribute('aria-expanded') !== 'true', { trigger: toggle }));
  close?.addEventListener('click', () => setMenuOpen(false));
  backdrop?.addEventListener('click', () => setMenuOpen(false));
  menu.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (link && !link.hasAttribute('target')) setMenuOpen(false, { restoreFocus: false });
  });
  document.addEventListener('keydown', (event) => {
    if (!menu.classList.contains('is-open')) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      setMenuOpen(false);
      return;
    }
    if (event.key !== 'Tab') return;
    const focusable = Array.from(menu.querySelectorAll(focusableSelector)).filter((element) => !element.closest('[hidden]'));
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });
  const syncMenuBreakpoint = () => {
    if (!mobileQuery.matches) {
      setMenuOpen(false, { restoreScroll: scrollLocked, restoreFocus: false });
      menu.inert = false;
      parents.forEach((item) => {
        const child = item.querySelector(':scope > ul');
        if (child) child.hidden = false;
      });
      return;
    }
    menu.inert = !menu.classList.contains('is-open');
    parents.forEach((item) => {
      const child = item.querySelector(':scope > ul');
      const button = item.querySelector(':scope > .submenu-toggle');
      if (child && button) child.hidden = button.getAttribute('aria-expanded') !== 'true';
    });
  };
  mobileQuery.addEventListener('change', syncMenuBreakpoint);
  window.addEventListener('resize', syncMenuBreakpoint, { passive: true });

  setupAccessibilityTools();
  setupSearchOverlay();
  setupCarousels();
  setupInstagramPostSliders();
  setupLiveClock();
  setupDynamicServiceHours();
  setupBackToTop();
  setupHeroNewsTabs();
  setupHeroGreeting();
  setupHeroServiceStatus();
  setupHeroPrefetch();
  setupMaklumatLightbox();
  setupStickyNav();
  setupInstansiTabs();
  setupScrollReveal();
  setupCountUp();
  setupHeroBackdropPause();
  setupLazyIframes();
});

function setupLazyIframes() {
  const frames = document.querySelectorAll('.instagram-profile-card iframe[data-src]');
  if (!frames.length) return;
  const load = (frame) => {
    if (!frame.src && frame.dataset.src) {
      frame.src = frame.dataset.src;
    }
  };
  if (!('IntersectionObserver' in window)) {
    frames.forEach(load);
    return;
  }
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        load(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { rootMargin: '400px 0px' });
  frames.forEach((frame) => observer.observe(frame));
}

function setupHeroBackdropPause() {
  const hero = document.querySelector('.hero.home-slider');
  if (!hero || !('IntersectionObserver' in window)) return;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      hero.classList.toggle('is-offstage', !entry.isIntersecting);
    });
  }, { threshold: 0 });
  observer.observe(hero);
}

function setupStickyNav() {
  const nav = document.querySelector('.main-menu');
  if (!nav) return;
  const syncHeight = () => document.documentElement.style.setProperty('--nav-height', nav.offsetHeight + 'px');
  let threshold = 0;
  const syncThreshold = () => {
    if (!document.body.classList.contains('nav-stuck')) {
      threshold = nav.getBoundingClientRect().top + window.scrollY;
    }
  };
  const onScroll = () => {
    const stuck = window.scrollY > threshold + 8;
    if (stuck !== document.body.classList.contains('nav-stuck')) {
      syncHeight();
      document.body.classList.toggle('nav-stuck', stuck);
    }
  };
  syncHeight();
  syncThreshold();
  window.addEventListener('resize', () => { document.body.classList.remove('nav-stuck'); syncThreshold(); syncHeight(); onScroll(); }, { passive: true });
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('load', onScroll);
  onScroll();
}

function setupInstansiTabs() {
  document.querySelectorAll('.instansi-tab-board').forEach((board) => {
    const tabs = Array.from(board.querySelectorAll('[data-instansi-tab]'));
    const panels = Array.from(board.querySelectorAll('.instansi-panel'));
    if (!tabs.length) return;
    const activate = (tab) => {
      tabs.forEach((t) => {
        const active = t === tab;
        t.classList.toggle('is-active', active);
        t.setAttribute('aria-selected', String(active));
      });
      panels.forEach((p) => {
        const active = p.id === 'instansi-panel-' + tab.dataset.instansiTab;
        p.classList.toggle('is-active', active);
        p.hidden = !active;
      });
    };
    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => activate(tab));
      tab.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        const next = tabs[(index + (event.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
        next.focus();
        activate(next);
      });
    });
  });
}

function setupScrollReveal() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
    return;
  }
  const targets = document.querySelectorAll('.home-juknis-main > *, .home-juknis-main .home-content-pair > *, .home-juknis-main .home-briefing-pair > *, .home-juknis-sidebar > *');
  if (!targets.length) return;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-revealed');
        observer.unobserve(entry.target);
      }
    });
  }, { rootMargin: '0px 0px 200px 0px', threshold: 0 });
  targets.forEach((el) => {
    if (el.classList.contains('home-content-pair') || el.classList.contains('home-briefing-pair')) return;
    el.classList.add('reveal-init');
    observer.observe(el);
  });
}

function setupCountUp() {
  const nums = document.querySelectorAll('.stats-num[data-countup]');
  if (!nums.length) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
    return;
  }
  const animate = (el) => {
    const target = parseInt(el.dataset.countup, 10) || 0;
    const duration = 900;
    const startTime = performance.now();
    const tick = (now) => {
      const progress = Math.min((now - startTime) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(target * eased).toLocaleString('id-ID');
      if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  };
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        animate(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.4 });
  nums.forEach((el) => observer.observe(el));
}

function setupDynamicServiceHours() {
  const elements = document.querySelectorAll('#dynamic-service-hours, .js-service-hours');
  if (!elements.length) return;

  // Dapatkan hari ini dalam waktu Jakarta
  const today = new Date().toLocaleString('en-US', { timeZone: 'Asia/Jakarta', weekday: 'long' });

  let hours;
  if (today === 'Friday') {
    hours = '08.00-17.00 WIB';
  } else if (today === 'Saturday' || today === 'Sunday') {
    hours = 'Tutup (Libur Akhir Pekan)';
  } else {
    // Monday, Tuesday, Wednesday, Thursday
    hours = '08.00-16.30 WIB';
  }
  elements.forEach((element) => {
    element.textContent = hours;
  });
}

function setupLiveClock() {
  const clockDate = document.getElementById('live-clock-date');
  const clockTime = document.getElementById('live-clock-time');

  if (clockDate && clockTime) {
    const formatterDate = new Intl.DateTimeFormat('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });

    const formatterTime = new Intl.DateTimeFormat('id-ID', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false
    });

    function updateClock() {
      const now = new Date();
      clockDate.textContent = formatterDate.format(now);
      clockTime.textContent = formatterTime.format(now).replace(/\./g, ':') + ' WIB';
    }

    updateClock();
    setInterval(updateClock, 1000);
  }
}

const accessStorageKeys = [
  'pnNatunaFontScale',
  'pnNatunaTextSpacing',
  'pnNatunaDark',
  'pnNatunaInvertColours',
  'pnNatunaGreyHues',
  'pnNatunaUnderlineLinks',
  'pnNatunaBigCursor',
  'pnNatunaReadingGuide',
  'pnNatunaVoiceReader',
  'pnNatunaVoiceName',
  'pnNatunaContrast',
  '_accessState'
];

function storageGet(key, fallback = '') {
  try {
    return localStorage.getItem(key) ?? fallback;
  } catch (error) {
    return fallback;
  }
}

function storageSet(key, value) {
  try {
    localStorage.setItem(key, value);
  } catch (error) {
    // Storage can be blocked by privacy settings; UI state still works for the page session.
  }
}

function storageRemove(key) {
  try {
    localStorage.removeItem(key);
  } catch (error) {
    // Storage can be blocked by privacy settings; reset should still update DOM state.
  }
}

function clearAccessibilityStorage() {
  accessStorageKeys.forEach(storageRemove);
}

function setupAccessibilityTools() {
  const root = document.documentElement;
  const body = document.body;
  const savedScale = Number(storageGet('pnNatunaFontScale', '0'));
  const savedSpacing = Number(storageGet('pnNatunaTextSpacing', '0'));
  const savedDark = storageGet('pnNatunaDark') === '1';
  const savedInvert = storageGet('pnNatunaInvertColours') === '1';
  const savedGrey = storageGet('pnNatunaGreyHues') === '1';
  const savedUnderline = storageGet('pnNatunaUnderlineLinks') === '1';
  const savedCursor = storageGet('pnNatunaBigCursor') === '1';
  const savedReadingGuide = storageGet('pnNatunaReadingGuide') === '1';
  if (storageGet('_accessState')) {
    storageRemove('_accessState');
    body.style.removeProperty('font-size');
    body.style.removeProperty('word-spacing');
    body.style.removeProperty('letter-spacing');
  }

  const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
  const setPressed = (selector, active) => {
    document.querySelectorAll(selector).forEach((button) => button.setAttribute('aria-pressed', String(active)));
  };

  const applyScale = (scale) => {
    const next = clamp(scale, -2, 3);
    root.style.setProperty('--font-scale-adjust', `${next * 0.0625}rem`);
    storageSet('pnNatunaFontScale', String(next));
  };

  const applySpacing = (spacing) => {
    const next = clamp(spacing, 0, 3);
    body.classList.toggle('access-text-spacing', next > 0);
    root.style.setProperty('--access-letter-spacing', next > 0 ? `${next * 0.04}em` : 'normal');
    root.style.setProperty('--access-word-spacing', next > 0 ? `${next * 0.16}em` : 'normal');
    storageSet('pnNatunaTextSpacing', String(next));
  };

  const setReadingGuide = (active) => {
    let guide = document.querySelector('.access-reading-guide');
    if (active && !guide) {
      guide = document.createElement('div');
      guide.className = 'access-reading-guide';
      guide.setAttribute('aria-hidden', 'true');
      document.body.appendChild(guide);
    }
    if (!active) guide?.remove();
    setPressed('[data-access-action="readingGuide"]', active);
    storageSet('pnNatunaReadingGuide', active ? '1' : '0');
  };

  const resetAccessibility = () => {
    clearAccessibilityStorage();
    applyScale(0);
    applySpacing(0);
    body.classList.remove('is-dark', 'access-underline-links', 'access-links-highlight');
    root.classList.remove('access-invert', 'access-grey', 'access-big-cursor');
    setReadingGuide(false);
    setPressed('.access-panel-dark, .dark-toggle', false);
    setPressed('[data-access-action="invertColors"]', false);
    setPressed('[data-access-action="grayHues"]', false);
    setPressed('[data-access-action="underlineLinks"]', false);
    setPressed('[data-access-action="bigCursor"]', false);
    const voiceButton = document.querySelector('.access-panel-voice');
    voiceButton?.setAttribute('aria-pressed', 'false');
    voiceButton?.classList.remove('is-active');
    window.speechSynthesis?.cancel();
    clearAccessibilityStorage();
    document.dispatchEvent(new Event('pnNatunaVoiceReset'));
  };

  applyScale(savedScale);
  applySpacing(savedSpacing);
  body.classList.remove('is-contrast');
  storageRemove('pnNatunaContrast');
  body.classList.toggle('is-dark', savedDark);
  root.classList.toggle('access-invert', savedInvert);
  root.classList.toggle('access-grey', savedGrey);
  body.classList.toggle('access-underline-links', savedUnderline);
  body.classList.toggle('access-links-highlight', savedUnderline);
  root.classList.toggle('access-big-cursor', savedCursor);
  setPressed('.access-panel-dark, .dark-toggle', savedDark);
  setPressed('[data-access-action="invertColors"]', savedInvert);
  setPressed('[data-access-action="grayHues"]', savedGrey);
  setPressed('[data-access-action="underlineLinks"]', savedUnderline);
  setPressed('[data-access-action="bigCursor"]', savedCursor);
  setReadingGuide(savedReadingGuide);

  const panelToggle = document.querySelector('.access-panel-toggle');
  const panelBody = document.querySelector('.access-panel-body');
  const panelClose = document.querySelector('.access-panel-close');
  const darkButtons = document.querySelectorAll('.dark-toggle, .access-panel-dark');

  const setPanelOpen = (open) => {
    if (!panelBody || !panelToggle) return;
    panelBody.hidden = !open;
    panelToggle.classList.toggle('is-active', open);
    panelToggle.setAttribute('aria-expanded', String(open));
    document.body.classList.toggle('access-panel-open', open);
  };

  panelToggle?.addEventListener('click', () => setPanelOpen(panelBody?.hidden !== false));
  panelClose?.addEventListener('click', () => setPanelOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setPanelOpen(false);
  });

  document.querySelectorAll('.access-panel-action').forEach((button) => {
    button.addEventListener('click', () => {
      const action = button.getAttribute('data-access-action');
      if (action === 'increaseText') applyScale(Number(storageGet('pnNatunaFontScale', '0')) + 1);
      if (action === 'decreaseText') applyScale(Number(storageGet('pnNatunaFontScale', '0')) - 1);
      if (action === 'increaseTextSpacing') applySpacing(Number(storageGet('pnNatunaTextSpacing', '0')) + 1);
      if (action === 'decreaseTextSpacing') applySpacing(Number(storageGet('pnNatunaTextSpacing', '0')) - 1);
      if (action === 'invertColors') {
        const active = !root.classList.contains('access-invert');
        root.classList.toggle('access-invert', active);
        setPressed('[data-access-action="invertColors"]', active);
        storageSet('pnNatunaInvertColours', active ? '1' : '0');
      }
      if (action === 'grayHues') {
        const active = !root.classList.contains('access-grey');
        root.classList.toggle('access-grey', active);
        setPressed('[data-access-action="grayHues"]', active);
        storageSet('pnNatunaGreyHues', active ? '1' : '0');
      }
      if (action === 'underlineLinks') {
        const active = !body.classList.contains('access-underline-links');
        body.classList.toggle('access-underline-links', active);
        body.classList.toggle('access-links-highlight', active);
        setPressed('[data-access-action="underlineLinks"]', active);
        storageSet('pnNatunaUnderlineLinks', active ? '1' : '0');
      }
      if (action === 'bigCursor') {
        const active = !root.classList.contains('access-big-cursor');
        root.classList.toggle('access-big-cursor', active);
        setPressed('[data-access-action="bigCursor"]', active);
        storageSet('pnNatunaBigCursor', active ? '1' : '0');
      }
      if (action === 'readingGuide') setReadingGuide(!document.querySelector('.access-reading-guide'));
      if (action === 'reset') resetAccessibility();
    });
  });

  document.addEventListener('mousemove', (event) => {
    const guide = document.querySelector('.access-reading-guide');
    if (!guide) return;
    guide.style.transform = `translateY(${Math.max(0, event.clientY - 6)}px)`;
  });

  darkButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const active = !body.classList.contains('is-dark');
      body.classList.toggle('is-dark', active);
      setPressed('.access-panel-dark, .dark-toggle', active);
      storageSet('pnNatunaDark', active ? '1' : '0');
    });
  });

  setupVoiceReader();
}

function setupVoiceReader() {
  const button = document.querySelector('.access-panel-voice');
  const selectWrap = document.querySelector('.access-panel-voice-select-wrap');
  const select = document.querySelector('.access-panel-voice-select');
  const note = document.querySelector('.access-panel-voice-note');
  const synth = window.speechSynthesis;

  if (!button) return;

  if (!('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
    button.disabled = true;
    button.textContent = 'Suara tidak didukung browser ini';
    button.setAttribute('aria-pressed', 'false');
    return;
  }

  let voices = [];
  let selectedVoice = null;
  let enabled = storageGet('pnNatunaVoiceReader') === '1';
  let lastText = '';
  let hoverTimer = null;

  const normalize = (text) => (text || '').replace(/\s+/g, ' ').trim();

  const isVisible = (element) => {
    if (!element || element.closest('[data-access-panel]') || element.closest('script, style, meta, link, template')) return false;
    if (element.closest('[aria-hidden="true"]')) return false;
    const rect = element.getBoundingClientRect();
    const style = window.getComputedStyle(element);
    return rect.width > 0 && rect.height > 0 && style.visibility !== 'hidden' && style.display !== 'none';
  };

  const getText = (element) => {
    if (!isVisible(element)) return '';
    const text = normalize(
      element.getAttribute('aria-label') ||
      element.getAttribute('title') ||
      element.getAttribute('alt') ||
      element.innerText ||
      element.textContent
    );
    if (!text) return '';
    return text.length > 180 ? `${text.slice(0, 177)}…` : text;
  };

  const chooseVoice = (savedName) => {
    if (!voices.length) return null;
    const saved = savedName ? voices.find((voice) => voice.name === savedName) : null;
    if (saved) return saved;
    const indonesian = voices.filter((voice) => /^id(-|$)/i.test(voice.lang || ''));
    return indonesian.find((voice) => voice.localService) ||
      indonesian[0] ||
      voices.find((voice) => voice.default) ||
      voices[0] ||
      null;
  };

  const renderVoiceOptions = () => {
    if (!select || !selectWrap) return;
    select.innerHTML = '';
    if (voices.length < 2) {
      selectWrap.hidden = true;
    } else {
      voices.forEach((voice) => {
        const option = document.createElement('option');
        option.value = voice.name;
        option.textContent = `${voice.name} (${voice.lang || 'default'})`;
        option.selected = selectedVoice && voice.name === selectedVoice.name;
        select.appendChild(option);
      });
      selectWrap.hidden = false;
    }
    const hasIndonesian = voices.some((voice) => /^id(-|$)/i.test(voice.lang || ''));
    if (note) note.hidden = hasIndonesian || !voices.length;
  };

  const loadVoices = () => {
    voices = synth.getVoices ? synth.getVoices() : [];
    selectedVoice = chooseVoice(storageGet('pnNatunaVoiceName'));
    renderVoiceOptions();
  };

  const speakText = (text) => {
    const clean = normalize(text);
    if (!enabled || !clean || clean === lastText) return;
    loadVoices();
    lastText = clean;
    synth.cancel();
    const utterance = new SpeechSynthesisUtterance(clean);
    if (selectedVoice) utterance.voice = selectedVoice;
    utterance.lang = selectedVoice?.lang || 'id-ID';
    utterance.rate = 0.94;
    utterance.pitch = 1;
    utterance.volume = 1;
    synth.speak(utterance);
  };

  const disableVoiceSession = () => {
    enabled = false;
    button.classList.remove('is-active');
    button.setAttribute('aria-pressed', 'false');
    synth.cancel();
    lastText = '';
  };

  const setEnabled = (active, announce) => {
    if (!active) {
      disableVoiceSession();
      storageSet('pnNatunaVoiceReader', '0');
      return;
    }
    enabled = true;
    button.classList.add('is-active');
    button.setAttribute('aria-pressed', 'true');
    storageSet('pnNatunaVoiceReader', '1');
    if (announce) speakText('Selamat datang di Pengadilan Negeri Natuna Kelas II.');
  };

  document.addEventListener('pnNatunaVoiceReset', disableVoiceSession);

  button.addEventListener('click', () => setEnabled(!enabled, true));

  select?.addEventListener('change', (event) => {
    storageSet('pnNatunaVoiceName', event.currentTarget.value);
    loadVoices();
    lastText = '';
    if (enabled) speakText('Pilihan suara diperbarui.');
  });

  document.addEventListener('pointerover', (event) => {
    if (!enabled) return;
    window.clearTimeout(hoverTimer);
    hoverTimer = window.setTimeout(() => speakText(getText(event.target)), 250);
  });

  document.addEventListener('focusin', (event) => {
    if (!enabled) return;
    window.clearTimeout(hoverTimer);
    hoverTimer = window.setTimeout(() => speakText(getText(event.target)), 120);
  });

  if ('onvoiceschanged' in synth) {
    synth.addEventListener('voiceschanged', loadVoices);
  }

  loadVoices();
  setEnabled(enabled, false);

  if (enabled) {
    window.setTimeout(() => speakText('Selamat datang di Pengadilan Negeri Natuna Kelas II.'), 600);
  }
}

function setupSearchOverlay() {
  const overlay = document.querySelector('.search-overlay');
  const toggle = document.querySelector('.search-overlay-toggle');
  const close = document.querySelector('.search-overlay-close');
  const input = document.querySelector('#site-search-query');

  if (!overlay || !toggle) {
    return;
  }

  const setOpen = (open) => {
    overlay.hidden = !open;
    document.body.classList.toggle('search-overlay-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    if (open) {
      window.setTimeout(() => input?.focus(), 40);
    }
  };

  toggle.addEventListener('click', () => setOpen(overlay.hidden));
  close?.addEventListener('click', () => setOpen(false));
  overlay.addEventListener('click', (event) => {
    if (event.target === overlay) {
      setOpen(false);
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !overlay.hidden) {
      setOpen(false);
    }
  });
}

function initCarousel(root, opts) {
  const slides = Array.from(root.querySelectorAll(opts.slide));
  const dots = Array.from(root.querySelectorAll(opts.dot));
  const caption = opts.caption ? root.querySelector(opts.caption) : null;
  if (slides.length < 2) {
    return;
  }

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const interval = parseInt(root.dataset.interval || opts.interval || '5000', 10);
  root.style.setProperty('--ci', interval + 'ms');
  let activeIndex = 0;
  let timer = null;

  const setActive = (index) => {
    activeIndex = (index + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      slide.classList.toggle('is-active', i === activeIndex);
      slide.setAttribute('aria-hidden', String(i !== activeIndex));
    });
    dots.forEach((dot, i) => {
      dot.classList.toggle('is-active', i === activeIndex);
      dot.setAttribute('aria-pressed', String(i === activeIndex));
    });
    if (caption && slides[activeIndex]) {
      caption.textContent = slides[activeIndex].dataset.label || '';
    }
  };

  const stop = () => {
    if (timer) {
      window.clearInterval(timer);
      timer = null;
    }
  };

  const start = () => {
    if (reducedMotion) {
      return;
    }
    stop();
    timer = window.setInterval(() => setActive(activeIndex + 1), interval);
  };

  dots.forEach((dot, dotIndex) => {
    dot.addEventListener('click', () => {
      setActive(dotIndex);
      start();
    });
  });

  if (opts.nav) {
    root.querySelectorAll(opts.nav).forEach((btn) => {
      btn.addEventListener('click', () => {
        setActive(activeIndex + (parseInt(btn.dataset.heroNav, 10) || 1));
        start();
      });
    });
  }

  root.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
      setActive(activeIndex - 1);
      start();
    } else if (event.key === 'ArrowRight') {
      setActive(activeIndex + 1);
      start();
    }
  });

  let touchX = null;
  root.addEventListener('touchstart', (event) => {
    touchX = event.touches[0].clientX;
  }, { passive: true });
  root.addEventListener('touchend', (event) => {
    if (touchX === null) {
      return;
    }
    const delta = event.changedTouches[0].clientX - touchX;
    if (Math.abs(delta) > 40) {
      setActive(activeIndex + (delta < 0 ? 1 : -1));
      start();
    }
    touchX = null;
  }, { passive: true });

  root.addEventListener('mouseenter', stop);
  root.addEventListener('mouseleave', start);

  setActive(0);
  start();
}

function setupCarousels() {
  document.querySelectorAll('.role-carousel').forEach((el) => {
    initCarousel(el, { slide: '.role-slide', dot: '[data-role-slide]', interval: '5000' });
  });
  document.querySelectorAll('.survey-carousel').forEach((el) => {
    initCarousel(el, { slide: '.survey-slide', dot: '[data-survey-slide]', caption: '.survey-caption' });
  });
  document.querySelectorAll('.hero-slider').forEach((el) => {
    initCarousel(el, { slide: '.hero-slide', dot: '[data-hero-slide]', interval: '6000', nav: '[data-hero-nav]' });
  });
}

function setupInstagramPostSliders() {
  document.querySelectorAll('.instagram-post-slider').forEach((slider) => {
    const slides = Array.from(slider.querySelectorAll('.instagram-post-slide'));
    const dots = Array.from(slider.querySelectorAll('[data-instagram-slide]'));
    const label = slider.querySelector('.instagram-slide-count');
    const interval = Number(slider.getAttribute('data-interval') || '5000');

    if (slides.length < 2) {
      return;
    }

    let activeIndex = 0;
    let timer = null;
    let embedsEnabled = false;

    const loadFrame = (slide) => {
      const frame = slide.querySelector('iframe');
      if (frame && !frame.src && frame.dataset.src) {
        frame.src = frame.dataset.src;
      }
    };

    const setActive = (index) => {
      activeIndex = (index + slides.length) % slides.length;
      slides.forEach((slide, slideIndex) => {
        const active = slideIndex === activeIndex;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', String(!active));
        if (embedsEnabled && active) {
          loadFrame(slide);
        }
      });
      dots.forEach((dot, dotIndex) => {
        dot.classList.toggle('is-active', dotIndex === activeIndex);
        dot.setAttribute('aria-pressed', String(dotIndex === activeIndex));
      });
      if (label) {
        label.textContent = `${activeIndex + 1}/${slides.length}`;
      }
    };

    const start = () => {
      if (!timer) {
        timer = window.setInterval(() => setActive(activeIndex + 1), interval);
      }
    };

    const enableEmbeds = () => {
      if (embedsEnabled) {
        return;
      }
      embedsEnabled = true;
      loadFrame(slides[activeIndex]);
      start();
    };

    dots.forEach((dot, dotIndex) => {
      dot.addEventListener('click', () => {
        if (timer) {
          window.clearInterval(timer);
          timer = null;
        }
        enableEmbeds();
        setActive(dotIndex);
        start();
      });
    });

    setActive(0);

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          enableEmbeds();
          observer.disconnect();
        }
      }, { rootMargin: '240px 0px' });
      observer.observe(slider);
    } else {
      window.setTimeout(enableEmbeds, 3000);
    }
  });
}

function setupBackToTop() {
  const btn = document.getElementById('back-to-top');
  if (!btn) return;
  btn.hidden = false;
  const onScroll = () => btn.classList.toggle('is-visible', window.scrollY > 480);
  window.addEventListener('scroll', onScroll, { passive: true });
  btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  onScroll();
}

function setupHeroNewsTabs() {
  const slide = document.querySelector('.hero-slide-news');
  if (!slide) {
    return;
  }
  const tabs = Array.from(slide.querySelectorAll('[data-hero-tab]'));
  const panels = Array.from(slide.querySelectorAll('[data-hero-panel]'));
  const preview = document.getElementById('hero-news-preview');
  const caption = document.getElementById('hero-news-caption');

  const setPreview = (link) => {
    if (!preview || !link) {
      return;
    }
    slide.querySelectorAll('.hero-tab-list a.is-preview').forEach((a) => a.classList.remove('is-preview'));
    link.classList.add('is-preview');
    const src = link.dataset.image || '';
    const cap = link.dataset.caption || '';
    if (!src || preview.getAttribute('src') === src) {
      if (caption && cap) {
        caption.textContent = cap;
      }
      return;
    }
    preview.classList.add('is-swapping');
    window.setTimeout(() => {
      preview.setAttribute('src', src);
      if (caption) {
        caption.textContent = cap;
      }
      preview.addEventListener('load', () => preview.classList.remove('is-swapping'), { once: true });
      window.setTimeout(() => preview.classList.remove('is-swapping'), 700);
    }, 180);
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      tabs.forEach((t) => {
        t.classList.toggle('is-active', t === tab);
        t.setAttribute('aria-selected', String(t === tab));
      });
      panels.forEach((p) => p.classList.toggle('is-active', p.dataset.heroPanel === tab.dataset.heroTab));
      const activePanel = panels.find((p) => p.dataset.heroPanel === tab.dataset.heroTab);
      setPreview(activePanel ? activePanel.querySelector('a[data-image]') : null);
    });
  });

  slide.querySelectorAll('.hero-tab-list a[data-image]').forEach((link) => {
    link.addEventListener('mouseenter', () => setPreview(link));
    link.addEventListener('focus', () => setPreview(link));
  });

  const first = slide.querySelector('.hero-tab-list.is-active a[data-image]');
  if (first) {
    first.classList.add('is-preview');
  }
}

function pnNatunaJakartaNow() {
  return new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
}

function setupHeroGreeting() {
  const el = document.getElementById('hero-greeting');
  if (!el) {
    return;
  }
  const now = pnNatunaJakartaNow();
  const h = now.getHours();
  let greet = 'Selamat Malam';
  if (h >= 4 && h < 11) {
    greet = 'Selamat Pagi';
  } else if (h >= 11 && h < 15) {
    greet = 'Selamat Siang';
  } else if (h >= 15 && h < 19) {
    greet = 'Selamat Sore';
  }
  const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  el.textContent = greet + ' \u2014 ' + days[now.getDay()] + ', ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
}

function setupHeroServiceStatus() {
  const el = document.getElementById('hero-service-status');
  if (!el) {
    return;
  }
  const now = pnNatunaJakartaNow();
  const day = now.getDay();
  const minutes = now.getHours() * 60 + now.getMinutes();
  let open = false;
  let label = '';

  if (day >= 1 && day <= 4) {
    open = minutes >= 480 && minutes < 990;
    label = open ? 'PTSP Buka \u2014 tutup 16.30 WIB' : 'PTSP Tutup \u2014 buka ' + (minutes < 480 ? 'hari ini 08.00 WIB' : 'besok 08.00 WIB');
  } else if (day === 5) {
    open = minutes >= 480 && minutes < 1020;
    label = open ? 'PTSP Buka \u2014 tutup 17.00 WIB' : 'PTSP Tutup \u2014 buka ' + (minutes < 480 ? 'hari ini 08.00 WIB' : 'Senin 08.00 WIB');
  } else {
    label = 'PTSP Tutup \u2014 buka Senin 08.00 WIB';
  }

  el.textContent = label;
  el.classList.add(open ? 'is-open' : 'is-closed');
  el.hidden = false;
}

function setupMaklumatLightbox() {
  const triggers = Array.from(document.querySelectorAll('[data-maklumat-zoom]'));
  if (!triggers.length) {
    return;
  }

  let overlay = null;
  let lastFocus = null;

  const close = () => {
    if (!overlay || overlay.hidden) {
      return;
    }
    overlay.hidden = true;
    document.body.classList.remove('maklumat-lightbox-open');
    lastFocus?.focus();
  };

  const build = () => {
    overlay = document.createElement('div');
    overlay.className = 'maklumat-lightbox';
    overlay.hidden = true;
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML = '<button type="button" class="maklumat-lightbox-close" aria-label="Tutup pratinjau dokumen">×</button><figure><img alt=""><figcaption></figcaption></figure>';
    overlay.querySelector('.maklumat-lightbox-close').addEventListener('click', close);
    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) {
        close();
      }
    });
    document.body.appendChild(overlay);
  };

  const open = (trigger) => {
    if (!overlay) {
      build();
    }
    const image = overlay.querySelector('img');
    image.src = trigger.dataset.maklumatZoom;
    image.alt = trigger.dataset.maklumatLabel || '';
    overlay.querySelector('figcaption').textContent = trigger.dataset.maklumatLabel || '';
    overlay.hidden = false;
    document.body.classList.add('maklumat-lightbox-open');
    lastFocus = trigger;
    overlay.querySelector('.maklumat-lightbox-close').focus();
  };

  triggers.forEach((trigger) => trigger.addEventListener('click', () => open(trigger)));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      close();
    }
  });
}

function setupHeroPrefetch() {
  const run = () => {
    const urls = new Set();
    document.querySelectorAll('.hero-tab-list a[data-image]').forEach((a) => urls.add(a.dataset.image));
    document.querySelectorAll('.hero-slide img').forEach((img) => urls.add(img.getAttribute('src')));
    urls.forEach((src) => {
      if (src) {
        const im = new Image();
        im.src = src;
      }
    });
  };
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(run, { timeout: 4000 });
  } else {
    window.setTimeout(run, 2500);
  }
}

