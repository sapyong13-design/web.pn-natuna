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
  setupRoleCarousel();
  setupInstagramPostSliders();
  setupLiveClock();
  setupDynamicServiceHours();
  setupBackToTop();
  setupSurveyCarousel();
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

function setupRoleCarousel() {
  document.querySelectorAll('.role-carousel').forEach((carousel) => {
    const slides = Array.from(carousel.querySelectorAll('.role-slide'));
    const dots = Array.from(carousel.querySelectorAll('[data-role-slide]'));
    if (slides.length < 2) {
      return;
    }

    let activeIndex = 0;
    let timer = null;

    const setActive = (index) => {
      activeIndex = (index + slides.length) % slides.length;
      slides.forEach((slide, slideIndex) => {
        slide.classList.toggle('is-active', slideIndex === activeIndex);
        slide.setAttribute('aria-hidden', String(slideIndex !== activeIndex));
      });
      dots.forEach((dot, dotIndex) => {
        dot.classList.toggle('is-active', dotIndex === activeIndex);
        dot.setAttribute('aria-pressed', String(dotIndex === activeIndex));
      });
    };

    const start = () => {
      timer = window.setInterval(() => setActive(activeIndex + 1), 5000);
    };

    dots.forEach((dot, dotIndex) => {
      dot.addEventListener('click', () => {
        if (timer) {
          window.clearInterval(timer);
        }
        setActive(dotIndex);
        start();
      });
    });

    setActive(0);
    start();
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

    const setActive = (index) => {
      activeIndex = (index + slides.length) % slides.length;
      slides.forEach((slide, slideIndex) => {
        const active = slideIndex === activeIndex;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', String(!active));
        const frame = slide.querySelector('iframe');
        if (frame && active && !frame.src) {
          frame.src = frame.dataset.src || '';
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
      timer = window.setInterval(() => setActive(activeIndex + 1), interval);
    };

    dots.forEach((dot, dotIndex) => {
      dot.addEventListener('click', () => {
        if (timer) {
          window.clearInterval(timer);
        }
        setActive(dotIndex);
        start();
      });
    });

    setActive(0);
    start();
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

function setupSurveyCarousel() {
  document.querySelectorAll('.survey-carousel').forEach((carousel) => {
    const slides = Array.from(carousel.querySelectorAll('.survey-slide'));
    const dots = Array.from(carousel.querySelectorAll('[data-survey-slide]'));
    const caption = carousel.querySelector('.survey-caption');
    if (slides.length < 2) {
      return;
    }
    let activeIndex = 0;
    let timer = null;
    const interval = parseInt(carousel.dataset.interval || '5000', 10);

    const setActive = (index) => {
      activeIndex = (index + slides.length) % slides.length;
      slides.forEach((slide, i) => slide.classList.toggle('is-active', i === activeIndex));
      dots.forEach((dot, i) => dot.classList.toggle('is-active', i === activeIndex));
      if (caption && slides[activeIndex]) {
        caption.textContent = slides[activeIndex].dataset.label || '';
      }
    };

    const start = () => {
      timer = window.setInterval(() => setActive(activeIndex + 1), interval);
    };

    dots.forEach((dot, dotIndex) => {
      dot.addEventListener('click', () => {
        if (timer) {
          window.clearInterval(timer);
        }
        setActive(dotIndex);
        start();
      });
    });

    setActive(0);
    start();
  });
}
