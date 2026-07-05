document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.menu-toggle');
  const menu = document.querySelector('.main-menu-list');
  const close = document.querySelector('.menu-close');
  const backdrop = document.querySelector('.menu-backdrop');

  if (!toggle || !menu) {
    setupAccessibilityTools();
    return;
  }

  const setMenuOpen = (open) => {
    toggle.setAttribute('aria-expanded', String(open));
    menu.classList.toggle('is-open', open);
    document.body.classList.toggle('menu-drawer-open', open);
    if (backdrop) {
      backdrop.hidden = !open;
    }
  };

  toggle.addEventListener('click', () => setMenuOpen(toggle.getAttribute('aria-expanded') !== 'true'));
  close?.addEventListener('click', () => setMenuOpen(false));
  backdrop?.addEventListener('click', () => setMenuOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      setMenuOpen(false);
    }
  });

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
});

function setupDynamicServiceHours() {
  const element = document.getElementById('dynamic-service-hours');
  if (!element) return;

  // Dapatkan hari ini dalam waktu Jakarta
  const today = new Date().toLocaleString('en-US', { timeZone: 'Asia/Jakarta', weekday: 'long' });
  
  if (today === 'Friday') {
    element.textContent = '08.00-17.00 WIB';
  } else if (today === 'Saturday' || today === 'Sunday') {
    element.textContent = 'Tutup (Libur Akhir Pekan)';
  } else {
    // Monday, Tuesday, Wednesday, Thursday
    element.textContent = '08.00-16.30 WIB';
  }
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

function setupAccessibilityTools() {
  const root = document.documentElement;
  const body = document.body;
  const savedScale = Number(localStorage.getItem('pnNatunaFontScale') || '0');
  const savedContrast = localStorage.getItem('pnNatunaContrast') === '1';
  const savedDark = localStorage.getItem('pnNatunaDark') === '1';

  const applyScale = (scale) => {
    const next = Math.max(-1, Math.min(2, scale));
    root.style.setProperty('--font-scale-adjust', `${next * 0.0625}rem`);
    localStorage.setItem('pnNatunaFontScale', String(next));
  };

  applyScale(savedScale);
  body.classList.toggle('is-contrast', savedContrast);
  body.classList.toggle('is-dark', savedDark);

  document.querySelectorAll('.font-scale-button').forEach((button) => {
    button.addEventListener('click', () => {
      const current = Number(localStorage.getItem('pnNatunaFontScale') || '0');
      const mode = button.getAttribute('data-font-scale');
      applyScale(mode === 'reset' ? 0 : current + (mode === 'up' ? 1 : -1));
    });
  });

  document.querySelector('.contrast-toggle')?.addEventListener('click', (event) => {
    const active = !body.classList.contains('is-contrast');
    body.classList.toggle('is-contrast', active);
    event.currentTarget.setAttribute('aria-pressed', String(active));
    localStorage.setItem('pnNatunaContrast', active ? '1' : '0');
  });

  document.querySelector('.dark-toggle')?.addEventListener('click', (event) => {
    const active = !body.classList.contains('is-dark');
    body.classList.toggle('is-dark', active);
    event.currentTarget.setAttribute('aria-pressed', String(active));
    localStorage.setItem('pnNatunaDark', active ? '1' : '0');
  });
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

