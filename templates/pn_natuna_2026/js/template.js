document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.menu-toggle');
  const menu = document.querySelector('.main-menu-list');
  const close = document.querySelector('.menu-close');
  const backdrop = document.querySelector('.menu-backdrop');
  setupAmpuhDirectory();
  setupYouTubeShowcase();
  setupMobileMenuFilter();
  setupMobileRailStatus();
  const mobileQuery = window.matchMedia('(max-width: 760px)');

  if (!toggle || !menu) {
    setupAccessibilityTools();
    setupEditorialArticleShare();
    return;
  }

  let trigger = toggle;
  let lockedScrollY = 0;
  const focusableSelector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';
  const parents = Array.from(menu.querySelectorAll('li.parent, li.deeper')).filter((item) => {
    const child = Array.from(item.children).find((element) => element.tagName === 'UL');
    return Boolean(child);
  });
  const normalizeLabel = (value) => value.replace(/\s+/g, ' ').trim();
  parents.forEach((item) => {
    const child = Array.from(item.children).find((element) => element.tagName === 'UL');
    const link = Array.from(item.children).find((element) => element.matches('a[href]'));
    if (!child || !link || child.querySelector(':scope > .mobile-menu-summary-item')) return;
    const summaryItem = document.createElement('li');
    summaryItem.className = 'mobile-menu-summary-item';
    const summaryLink = document.createElement('a');
    summaryLink.className = 'mobile-menu-summary-link';
    summaryLink.href = link.href;
    summaryLink.textContent = `Ringkasan ${normalizeLabel(link.textContent)}`;
    summaryItem.appendChild(summaryLink);
    child.prepend(summaryItem);
  });
  const addMobileGroupLabels = (parentHref, groups) => {
    const parentLink = Array.from(menu.querySelectorAll(':scope a[href]')).find((link) => new URL(link.href).pathname === parentHref);
    const list = parentLink?.parentElement.querySelector(':scope > ul');
    if (!list) return;
    groups.forEach(({ label, before }) => {
      const target = Array.from(list.children).find((item) => {
        const link = item.querySelector(':scope > a[href]');
        return link && new URL(link.href).pathname.endsWith(`/${before}`);
      });
      if (!target || list.querySelector(`[data-mobile-menu-group="${before}"]`)) return;
      const heading = document.createElement('li');
      heading.className = 'mobile-menu-group-label';
      heading.dataset.mobileMenuGroup = before;
      heading.textContent = label;
      target.insertAdjacentElement('beforebegin', heading);
    });
  };
  addMobileGroupLabels('/transparansi', [
    { label: 'Akuntabilitas Kinerja', before: 'ringkasan-lkjip' },
    { label: 'Keuangan', before: 'laporan-realisasi-anggaran' },
    { label: 'Survei & Integritas', before: 'laporan-skm' },
    { label: 'Informasi Publik', before: 'e-brosur' },
  ]);
  addMobileGroupLabels('/informasi-perkara', [
    { label: 'Biaya & Prosedur', before: 'biaya-perkara' },
    { label: 'Data & Administrasi', before: 'data-eksekusi' },
  ]);


  const activeLink = menu.querySelector('a[aria-current="page"], li.current > a, li.active > a');
  if (activeLink && !activeLink.hasAttribute('aria-current')) activeLink.setAttribute('aria-current', 'page');
  const scrollActiveMenuItem = () => {
    if (!activeLink) return;
    const scroller = menu.querySelector('.mobile-menu-scroll');
    if (!scroller) return;
    const linkRect = activeLink.getBoundingClientRect();
    const scrollRect = scroller.getBoundingClientRect();
    if (linkRect.top < scrollRect.top || linkRect.bottom > scrollRect.bottom) {
      activeLink.scrollIntoView({ block: 'center', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    }
  };


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
      window.requestAnimationFrame(scrollActiveMenuItem);
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
  setupInstagramCarousels();
  setupLiveClock();
  setupDynamicServiceHours();
  setupBackToTop();
  setupHeroNewsTabs();
  setupHeroGreeting();
  setupHeroServiceStatus();
  setupMaklumatLightbox();
  setupStickyNav();
  setupInstansiTabs();
  setupScrollReveal();
  setupCountUp();
  setupHeroBackdropPause();
  setupLazyIframes();
  setupEditorialArticleShare();
});

function setupAmpuhDirectory() {
  const root = document.querySelector('[data-ampuh-directory]');
  if (!root) return;

  const toggles = Array.from(root.querySelectorAll('[data-ampuh-toggle]'));
  const items = Array.from(root.querySelectorAll('[data-search-text]'));
  const resultNodes = Array.from(root.querySelectorAll('[data-ampuh-file-result]'));
  const checklistNodes = Array.from(root.querySelectorAll('[data-ampuh-checklist]'));
  const subchecklistNodes = Array.from(root.querySelectorAll('[data-ampuh-subchecklist]'));
  const search = root.querySelector('[data-ampuh-search]');
  const filter = root.querySelector('[data-ampuh-gobi-filter]');
  const gobiSelect = root.querySelector('[data-ampuh-gobi-select]');
  const filterPrev = root.querySelector('[data-ampuh-filter-prev]');
  const filterNext = root.querySelector('[data-ampuh-filter-next]');
  const closeAll = root.querySelector('[data-ampuh-close-all]');
  const clearSearch = root.querySelector('[data-ampuh-clear-search]');
  const results = root.querySelector('[data-ampuh-results]');
  const tree = root.querySelector('.ampuh-directory__tree');
  let selectedGobi = '';

  const setExpanded = (toggle, expanded, animate = true) => {
    const panel = document.getElementById(toggle.getAttribute('aria-controls'));
    toggle.setAttribute('aria-expanded', String(expanded));
    toggle.closest('[data-search-text]')?.classList.toggle('is-expanded', expanded);
    if (!panel) return;
    if (!expanded) {
      panel.hidden = true;
      panel.classList.remove('is-revealing');
      return;
    }
    panel.hidden = false;
    if (!animate) return;
    panel.classList.add('is-revealing');
    const reveal = () => panel.classList.remove('is-revealing');
    if (typeof window.requestAnimationFrame === 'function') window.requestAnimationFrame(() => window.requestAnimationFrame(reveal));
    else reveal();
  };
  const closeEveryPanel = () => toggles.forEach((toggle) => setExpanded(toggle, false));
  const normalize = (value) => value.toLocaleLowerCase('id-ID').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
  const restoreFilename = (item) => {
    const name = item.querySelector('.ampuh-directory__file-name');
    if (!name) return;
    name.dataset.original ||= name.textContent;
    name.textContent = name.dataset.original;
  };
  const highlightFilename = (item, rawQuery) => {
    const name = item.querySelector('.ampuh-directory__file-name');
    if (!name || !rawQuery || typeof name.replaceChildren !== 'function') return;
    const original = name.dataset.original || name.textContent;
    const query = rawQuery.toLocaleLowerCase('id-ID').trim();
    const index = original.toLocaleLowerCase('id-ID').indexOf(query);
    if (index < 0) return;
    const mark = document.createElement('mark');
    mark.className = 'ampuh-directory__match';
    mark.textContent = original.slice(index, index + query.length);
    name.replaceChildren(document.createTextNode(original.slice(0, index)), mark, document.createTextNode(original.slice(index + query.length)));
  };
  const syncResults = (count, gobiCount, query, matchingChecklists = [], exactChecklist = null, matchingSubchecklists = []) => {
    if (results) {
      if (!query) results.textContent = '';
      else if (matchingChecklists.length && matchingSubchecklists.length) results.textContent = `${matchingChecklists.length} checklist + ${matchingSubchecklists.length} sub-checklist · ${gobiCount} GOBI`;
      else if (matchingSubchecklists.length) results.textContent = `${matchingSubchecklists.length} sub-checklist · ${gobiCount} GOBI`;
      else if (exactChecklist) results.textContent = `Checklist ${exactChecklist.getAttribute('data-ampuh-checklist')} · GOBI ${exactChecklist.closest('[data-ampuh-gobi]')?.getAttribute('data-ampuh-gobi')}`;
      else if (matchingChecklists.length) results.textContent = `${matchingChecklists.length} checklist · ${gobiCount} GOBI`;
      else results.textContent = count ? `${count} dokumen · ${gobiCount} GOBI` : 'Tidak ada hasil yang cocok.';
    }
    const empty = Boolean(query) && count === 0 && matchingChecklists.length === 0 && matchingSubchecklists.length === 0;
    if (results) results.classList.toggle('ampuh-directory__empty', empty);
    if (clearSearch) clearSearch.hidden = !query;
  };
  const showAncestors = (item) => {
    let parent = item.parentElement;
    while (parent && parent !== root) {
      parent.hidden = false;
      if (parent.matches('[data-ampuh-panel]')) {
        const toggle = toggles.find((candidate) => candidate.getAttribute('aria-controls') === parent.id);
        if (toggle) setExpanded(toggle, true, false);
      }
      parent = parent.parentElement;
    }
  };
  const apply = () => {
    const query = normalize(search?.value || '');
    resultNodes.forEach(restoreFilename);
    closeEveryPanel();
    items.forEach((item) => { item.hidden = true; });
    const matchingFiles = resultNodes.filter((item) => {
      const gobi = item.closest('[data-ampuh-gobi]');
      const inSelectedGobi = !selectedGobi || gobi?.getAttribute('data-ampuh-gobi') === selectedGobi;
      return inSelectedGobi && (!query || normalize(item.getAttribute('data-search-text') || item.textContent).includes(query));
    });
    const hierarchyTokens = query.split(/\s+/).filter(Boolean);
    const numericHierarchyQuery = hierarchyTokens.length > 0 && hierarchyTokens.every((token) => /^\d+(?:\.\d+)*$/.test(token));
    const checklistQuery = query.replace(/^checklist\s+/, '').trim();
    const matchingChecklists = checklistNodes.filter((item) => {
      const gobi = item.closest('[data-ampuh-gobi]');
      const inSelectedGobi = !selectedGobi || gobi?.getAttribute('data-ampuh-gobi') === selectedGobi;
      if (!query || !inSelectedGobi) return false;
      if (numericHierarchyQuery) return hierarchyTokens.includes(item.getAttribute('data-ampuh-checklist'));
      return normalize(item.getAttribute('data-search-text') || item.textContent).includes(query);
    });
    const exactChecklist = matchingChecklists.length === 1 && !numericHierarchyQuery && matchingChecklists[0].getAttribute('data-ampuh-checklist') === checklistQuery ? matchingChecklists[0] : (matchingChecklists.length === 1 && numericHierarchyQuery && hierarchyTokens.length === 1 ? matchingChecklists[0] : null);
    const matchingSubchecklists = subchecklistNodes.filter((item) => {
      const gobi = item.closest('[data-ampuh-gobi]');
      const inSelectedGobi = !selectedGobi || gobi?.getAttribute('data-ampuh-gobi') === selectedGobi;
      if (!query || !inSelectedGobi) return false;
      if (numericHierarchyQuery) return hierarchyTokens.includes(item.getAttribute('data-ampuh-subchecklist'));
      return normalize(item.getAttribute('data-search-text') || item.textContent).includes(query);
    });
    if (!query) {
      items.forEach((item) => {
        const gobi = item.closest('[data-ampuh-gobi]');
        item.hidden = Boolean(selectedGobi && gobi?.getAttribute('data-ampuh-gobi') !== selectedGobi);
      });
    } else if (matchingSubchecklists.length || matchingChecklists.length) {
      [...matchingChecklists, ...matchingSubchecklists].forEach((item) => {
        item.hidden = false;
        showAncestors(item);
        const toggle = item.querySelector('[data-ampuh-toggle]');
        if (toggle) setExpanded(toggle, true, false);
        item.querySelectorAll('[data-search-text]').forEach((descendant) => { descendant.hidden = false; });
      });
    } else {
      matchingFiles.forEach((item) => {
        item.hidden = false;
        showAncestors(item);
        highlightFilename(item, query);
      });
    }
    const resultItems = matchingChecklists.length || matchingSubchecklists.length ? [...matchingChecklists, ...matchingSubchecklists] : matchingFiles;
    const gobis = new Set(resultItems.map((item) => item.closest('[data-ampuh-gobi]')?.getAttribute('data-ampuh-gobi')).filter(Boolean));
    syncResults(matchingFiles.length, gobis.size, query, matchingChecklists, exactChecklist, matchingSubchecklists);
    if (tree) tree.classList.toggle('ampuh-directory__empty', Boolean(query) && matchingFiles.length === 0 && matchingChecklists.length === 0 && matchingSubchecklists.length === 0);
  };
  const setSelectedGobi = (value) => {
    selectedGobi = value;
    filter?.querySelectorAll('[data-ampuh-filter-value]').forEach((button) => button.setAttribute('aria-pressed', String(value !== '' && button.getAttribute('data-ampuh-filter-value') === value)));
    if (gobiSelect) gobiSelect.value = value;
    apply();
  };

  toggles.forEach((toggle) => toggle.addEventListener('click', () => setExpanded(toggle, toggle.getAttribute('aria-expanded') !== 'true')));
  closeAll?.addEventListener('click', closeEveryPanel);
  search?.addEventListener('input', apply);
  clearSearch?.addEventListener('click', () => {
    search.value = '';
    apply();
    search.focus();
  });
  filter?.querySelectorAll('[data-ampuh-filter-value]').forEach((button) => button.addEventListener('click', () => {
    const value = button.getAttribute('data-ampuh-filter-value');
    setSelectedGobi(selectedGobi === value ? '' : value);
  }));
  const scrollFilter = (direction) => {
    if (!filter || typeof filter.scrollBy !== 'function') return;
    const distance = Math.max(240, Math.round(filter.clientWidth * .72));
    filter.scrollBy({ left: direction * distance, behavior: 'smooth' });
  };
  filterPrev?.addEventListener('click', () => scrollFilter(-1));
  filterNext?.addEventListener('click', () => scrollFilter(1));
  gobiSelect?.addEventListener('change', () => setSelectedGobi(gobiSelect.value));
  apply();
}

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
  const hero = document.querySelector('.hero.home-slider');
  const syncHeight = () => document.documentElement.style.setProperty('--nav-height', nav.offsetHeight + 'px');
  let threshold = 0;
  let scrollFrame = 0;
  let scrollIdleTimer = 0;
  const syncThreshold = () => {
    if (!document.body.classList.contains('nav-stuck')) {
      threshold = nav.getBoundingClientRect().top + window.scrollY;
    }
  };
  const updateScrollState = () => {
    scrollFrame = 0;
    const stuck = window.scrollY > threshold + 8;
    if (stuck !== document.body.classList.contains('nav-stuck')) {
      syncHeight();
      document.body.classList.toggle('nav-stuck', stuck);
    }
    if (hero) {
      hero.classList.add('is-scroll-active');
      window.clearTimeout(scrollIdleTimer);
      scrollIdleTimer = window.setTimeout(() => hero.classList.remove('is-scroll-active'), 120);
    }
  };
  const onScroll = () => {
    if (!scrollFrame) scrollFrame = window.requestAnimationFrame(updateScrollState);
  };
  syncHeight();
  syncThreshold();
  window.addEventListener('resize', () => { document.body.classList.remove('nav-stuck'); syncThreshold(); syncHeight(); onScroll(); }, { passive: true });
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('load', onScroll);
  updateScrollState();
}

function setupInstansiTabs() {
  document.querySelectorAll('.instansi-tab-board').forEach((board) => {
    const tabs = Array.from(board.querySelectorAll('[data-instansi-tab]'));
    const panels = Array.from(board.querySelectorAll('.instansi-panel'));
    if (!tabs.length) return;
    const activate = (tab, moveFocus = false) => {
      tabs.forEach((t) => {
        const active = t === tab;
        t.classList.toggle('is-active', active);
        t.setAttribute('aria-selected', String(active));
        t.tabIndex = active ? 0 : -1;
      });
      panels.forEach((p) => {
        const active = p.id === 'instansi-panel-' + tab.dataset.instansiTab;
        p.classList.toggle('is-active', active);
        p.hidden = !active;
      });
      if (moveFocus) tab.focus();
    };
    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => activate(tab));
      tab.addEventListener('keydown', (event) => {
        let next = null;
        if (event.key === 'ArrowLeft') next = tabs[(index + tabs.length - 1) % tabs.length];
        if (event.key === 'ArrowRight') next = tabs[(index + 1) % tabs.length];
        if (event.key === 'Home') next = tabs[0];
        if (event.key === 'End') next = tabs[tabs.length - 1];
        if (!next) return;
        event.preventDefault();
        event.stopPropagation();
        activate(next, true);
      });
    });
    activate(tabs.find((tab) => tab.getAttribute('aria-selected') === 'true') || tabs[0]);
  });
}

function setupScrollReveal() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
    return;
  }
  // PERF-MOTION 2026-07-11: section-level containers only — one observed
  // element per card/band, never per inner item; pairs reveal as one unit.
  const targets = document.querySelectorAll('.home-juknis-main > *, .home-juknis-sidebar > *');
  if (!targets.length) return;
  const reveal = (el) => {
    el.classList.add('is-revealed');
    observer.unobserve(el);
  };
  // Fast flicks past content-visibility layout shifts can carry a section
  // through the viewport without a rendered frame; whenever anything observed
  // intersects, everything already above the viewport has been scrolled past
  // and must reveal too — nothing may stay stuck at opacity:0.
  const sweepAbove = () => {
    targets.forEach((el) => {
      if (!el.classList.contains('is-revealed') && el.getBoundingClientRect().bottom < 0) reveal(el);
    });
  };
  const observer = new IntersectionObserver((entries) => {
    let hit = false;
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      hit = true;
      if (entry.target.classList.contains('reveal-init')) reveal(entry.target);
      else observer.unobserve(entry.target);
    });
    if (hit) sweepAbove();
  }, { rootMargin: '0px 0px 200px 0px', threshold: 0 });
  targets.forEach((el) => {
    el.classList.add('reveal-init');
    observer.observe(el);
  });
  // Footer sentinel: catches a single mega-jump to page bottom where no
  // reveal target itself gets an intersection frame.
  const sentinel = document.querySelector('.site-footer');
  if (sentinel) observer.observe(sentinel);
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
function syncBrowserTheme(dark) {
  document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
  const theme = document.getElementById('theme-color-meta');
  if (theme) theme.content = dark ? '#151015' : '#8f1f0b';
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
  const syncDarkStatus = (active) => {
    document.querySelectorAll('.dark-toggle').forEach((button) => {
      const offStatus = button.querySelector('.dark-status-off');
      const onStatus = button.querySelector('.dark-status-on');
      if (offStatus) offStatus.hidden = active;
      if (onStatus) onStatus.hidden = !active;
    });
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
    syncBrowserTheme(false);
    body.classList.remove('is-dark', 'access-underline-links', 'access-links-highlight');
    root.classList.remove('access-invert', 'access-grey', 'access-big-cursor');
    setReadingGuide(false);
    setPressed('.access-panel-dark, .dark-toggle', false);
    syncDarkStatus(false);
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
  syncBrowserTheme(savedDark);
  body.classList.toggle('access-underline-links', savedUnderline);
  body.classList.toggle('access-links-highlight', savedUnderline);
  root.classList.toggle('access-big-cursor', savedCursor);
  setPressed('.access-panel-dark, .dark-toggle', savedDark);
  syncDarkStatus(savedDark);
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
      syncDarkStatus(active);
      syncBrowserTheme(active);
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
  const toggles = Array.from(document.querySelectorAll('.search-overlay-toggle'));
  const close = document.querySelector('.search-overlay-close');
  const input = document.querySelector('#site-search-query');
  const searchBackground = Array.from(document.body.children).filter((element) => element !== overlay && element.tagName !== 'SCRIPT');
  let searchReturnFocus = null;

  if (!overlay || !toggles.length) return;

  const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
  const trapFocus = (event, container) => {
    if (event.key !== 'Tab') return;
    const focusable = Array.from(container.querySelectorAll(focusableSelector)).filter((element) => !element.hidden && element.getClientRects().length);
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  };
  const setOpen = (open, trigger = null) => {
    if (open) searchReturnFocus = trigger || document.activeElement;
    overlay.hidden = !open;
    document.body.classList.toggle('search-overlay-open', open);
    toggles.forEach((toggle) => toggle.setAttribute('aria-expanded', String(open)));
    searchBackground.forEach((element) => element.toggleAttribute('inert', open));
    if (open) window.setTimeout(() => input?.focus(), 40);
    else if (searchReturnFocus instanceof HTMLElement) { searchReturnFocus.focus(); searchReturnFocus = null; }
  };

  toggles.forEach((toggle) => toggle.addEventListener('click', () => {
    const insideMobileMenu = Boolean(toggle.closest('.mobile-menu-panel'));
    if (insideMobileMenu) document.querySelector('.menu-close')?.click();
    setOpen(overlay.hidden, insideMobileMenu ? document.querySelector('.menu-toggle') : toggle);
  }));
  close?.addEventListener('click', () => setOpen(false));
  overlay.addEventListener('click', (event) => { if (event.target === overlay) setOpen(false); });
  overlay.addEventListener('keydown', (event) => trapFocus(event, overlay));
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !overlay.hidden) setOpen(false); });
}

function initCarousel(root, opts) {
  const slides = Array.from(root.querySelectorAll(opts.slide));
  const dots = Array.from(root.querySelectorAll(opts.dot));
  const caption = opts.caption ? root.querySelector(opts.caption) : null;
  if (slides.length < 2) return;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const interval = parseInt(root.dataset.interval || opts.interval || '5000', 10);
  root.style.setProperty('--ci', interval + 'ms');
  let activeIndex = 0;
  let timer = null;
  let userInteracted = false;

  const setActive = (index) => {
    activeIndex = (index + slides.length) % slides.length;
    slides.forEach((slide, i) => {
      const active = i === activeIndex;
      slide.classList.toggle('is-active', active);
      slide.setAttribute('aria-hidden', String(!active));
      slide.toggleAttribute('inert', !active);
    });
    dots.forEach((dot, i) => {
      dot.classList.toggle('is-active', i === activeIndex);
      dot.setAttribute('aria-pressed', String(i === activeIndex));
    });
    if (caption && slides[activeIndex]) caption.textContent = slides[activeIndex].dataset.label || '';
  };

  const stop = () => {
    if (!timer) return;
    window.clearInterval(timer);
    timer = null;
  };
  const start = () => {
    if (reducedMotion) return;
    stop();
    timer = window.setInterval(() => {
      if (root.closest('.is-scroll-active')) return;
      setActive(activeIndex + 1);
    }, interval);
  };

  const stopAfterInteraction = () => { userInteracted = true; stop(); };
  dots.forEach((dot, dotIndex) => {
    dot.addEventListener('click', () => {
      setActive(dotIndex);
      stopAfterInteraction();
    });
  });
  if (opts.nav) {
    root.querySelectorAll(opts.nav).forEach((button) => {
      button.addEventListener('click', () => {
        setActive(activeIndex + (parseInt(button.dataset.heroNav, 10) || 1));
        stopAfterInteraction();
      });
    });
  }
  root.addEventListener('keydown', (event) => {
    if (event.target instanceof Element && event.target.closest('a, button, input, select, textarea, [role="tablist"]')) return;
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
    setActive(activeIndex + (event.key === 'ArrowRight' ? 1 : -1));
    stopAfterInteraction();
  });

  let touchX = null;
  root.addEventListener('touchstart', (event) => {
    touchX = event.touches[0].clientX;
  }, { passive: true });
  root.addEventListener('touchend', (event) => {
    if (touchX === null) return;
    const delta = event.changedTouches[0].clientX - touchX;
    if (Math.abs(delta) > 40) {
      setActive(activeIndex + (delta < 0 ? 1 : -1));
      stopAfterInteraction();
    }
    touchX = null;
  }, { passive: true });

  root.addEventListener('mouseenter', stop);
  root.addEventListener('mouseleave', () => { if (!userInteracted && !root.matches(':focus-within')) start(); });
  root.addEventListener('focusin', stop);
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

function setupInstagramCarousels() {
  document.querySelectorAll('[data-instagram-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('.instagram-carousel-track');
    const slides = Array.from(carousel.querySelectorAll('.instagram-carousel-slide'));
    const dots = Array.from(carousel.querySelectorAll('[data-instagram-carousel-dot]'));
    const previous = carousel.querySelector('[data-instagram-carousel-prev]');
    const next = carousel.querySelector('[data-instagram-carousel-next]');
    const count = carousel.querySelector('.instagram-carousel-count');
    const status = carousel.querySelector('.instagram-carousel-status');
    if (!track || slides.length < 2 || !previous || !next) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let activeIndex = 0;
    let timer = null;
    let pointerStartX = null;
    let hovered = false;
    let focused = false;
    let intersecting = !('IntersectionObserver' in window);

    const canAutoplay = () => !reducedMotion.matches && !document.hidden && !hovered && !focused && intersecting;
    const stop = () => {
      if (timer !== null) {
        window.clearInterval(timer);
        timer = null;
      }
    };
    const start = () => {
      stop();
      if (canAutoplay()) timer = window.setInterval(() => setActive(activeIndex + 1, false), 5000);
    };
    const reset = () => start();
    const setActive = (index, announce) => {
      activeIndex = (index + slides.length) % slides.length;
      track.style.transform = `translateX(-${activeIndex * 100}%)`;
      slides.forEach((slide, slideIndex) => {
        const active = slideIndex === activeIndex;
        const link = slide.querySelector('a');
        slide.setAttribute('aria-hidden', String(!active));
        slide.toggleAttribute('inert', !active);
        if (link) link.tabIndex = active ? 0 : -1;
      });
      dots.forEach((dot, dotIndex) => {
        const active = dotIndex === activeIndex;
        dot.classList.toggle('is-active', active);
        dot.setAttribute('aria-current', String(active));
      });
      if (count) {
        const value = `${activeIndex + 1} dari ${slides.length}`;
        count.value = value;
        count.textContent = value;
      }
      if (announce && status) status.textContent = `Posting ${activeIndex + 1} dari ${slides.length}`;
    };
    const move = (offset) => { setActive(activeIndex + offset, true); reset(); };

    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    dots.forEach((dot, index) => dot.addEventListener('click', () => { setActive(index, true); reset(); }));
    carousel.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') { event.preventDefault(); move(-1); }
      if (event.key === 'ArrowRight') { event.preventDefault(); move(1); }
    });
    carousel.addEventListener('pointerdown', (event) => { pointerStartX = event.clientX; }, { passive: true });
    carousel.addEventListener('pointerup', (event) => {
      if (pointerStartX === null) return;
      const delta = event.clientX - pointerStartX;
      pointerStartX = null;
      if (Math.abs(delta) >= 40) move(delta < 0 ? 1 : -1);
    }, { passive: true });
    carousel.addEventListener('pointercancel', () => { pointerStartX = null; }, { passive: true });
    carousel.addEventListener('pointerenter', (event) => { if (event.pointerType === 'mouse') { hovered = true; stop(); } });
    carousel.addEventListener('pointerleave', (event) => { if (event.pointerType === 'mouse') { hovered = false; start(); } });
    carousel.addEventListener('focusin', () => { focused = true; stop(); });
    carousel.addEventListener('focusout', () => { window.setTimeout(() => { focused = carousel.contains(document.activeElement); start(); }, 0); });
    document.addEventListener('visibilitychange', start);
    reducedMotion.addEventListener('change', start);
    if ('IntersectionObserver' in window) {
      new IntersectionObserver((entries) => {
        const entry = entries.find((candidate) => candidate.target === carousel);
        intersecting = Boolean(entry?.isIntersecting && entry.intersectionRatio > 0);
        carousel.dataset.instagramIntersecting = String(intersecting);
        start();
      }, { threshold: 0.01 }).observe(carousel);
    }
    setActive(0, false);
    const initialRect = carousel.getBoundingClientRect();
    intersecting = initialRect.right > 0 && initialRect.left < window.innerWidth && initialRect.bottom > 0 && initialRect.top < window.innerHeight;
    carousel.dataset.instagramIntersecting = String(intersecting);
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
  const onScroll = () => btn.classList.toggle('is-visible', window.scrollY > 900);
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


  const activateTab = (tab, moveFocus = false) => {
    tabs.forEach((t) => {
      const active = t === tab;
      t.classList.toggle('is-active', active);
      t.setAttribute('aria-selected', String(active));
      t.tabIndex = active ? 0 : -1;
    });
    panels.forEach((panel) => {
      const active = panel.dataset.heroPanel === tab.dataset.heroTab;
      panel.classList.toggle('is-active', active);
      panel.hidden = !active;
    });
    const activePanel = panels.find((panel) => panel.dataset.heroPanel === tab.dataset.heroTab);
    setPreview(activePanel ? activePanel.querySelector('a[data-image]') : null);
    if (moveFocus) tab.focus();
  };
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activateTab(tab));
    tab.addEventListener('keydown', (event) => {
      let next = null;
      if (event.key === 'ArrowLeft') next = tabs[(index + tabs.length - 1) % tabs.length];
      if (event.key === 'ArrowRight') next = tabs[(index + 1) % tabs.length];
      if (event.key === 'Home') next = tabs[0];
      if (event.key === 'End') next = tabs[tabs.length - 1];
      if (!next) return;
      event.preventDefault();
      event.stopPropagation();
      activateTab(next, true);
    });
  });

  slide.querySelectorAll('.hero-tab-list a[data-image]').forEach((link) => {
    link.addEventListener('mouseenter', () => setPreview(link));
    link.addEventListener('focus', () => setPreview(link));
  });

  activateTab(tabs.find((tab) => tab.getAttribute('aria-selected') === 'true') || tabs[0]);
}

function setupMobileMenuFilter() {
  const input = document.querySelector('[data-mobile-menu-filter]');
  const clear = document.querySelector('[data-mobile-menu-clear]');
  const empty = document.querySelector('[data-mobile-menu-empty]');
  const status = document.querySelector('[data-mobile-menu-filter-status]');
  const root = document.querySelector('.mobile-menu-scroll > ul');
  if (!input || !root) return;
  const topItems = Array.from(root.children);
  const normalize = (value) => value.toLocaleLowerCase('id-ID').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  const apply = () => {
    const query = normalize(input.value.trim());
    let matches = 0;
    topItems.forEach((item) => {
      const ownMatch = normalize(item.textContent).includes(query);
      item.hidden = Boolean(query && !ownMatch);
      if (!item.hidden) matches += 1;
      if (query && ownMatch) {
        item.querySelectorAll(':scope .submenu-toggle').forEach((button) => button.setAttribute('aria-expanded', 'true'));
        item.querySelectorAll(':scope ul').forEach((list) => { list.hidden = false; });
      }
    });
    if (clear) clear.hidden = query === '';
    if (empty) empty.hidden = matches > 0;
    if (status) status.textContent = query ? `${matches} kelompok menu cocok` : 'Semua menu ditampilkan';
  };
  input.addEventListener('input', apply);
  clear?.addEventListener('click', () => { input.value = ''; apply(); input.focus(); });
}

function setupMobileRailStatus() {
  const bind = (rail, status, itemSelector) => {
    if (!rail || !status) return;
    const items = Array.from(rail.querySelectorAll(itemSelector));
    const output = status.querySelector('output');
    if (!items.length || !output) return;
    const update = () => {
      const center = rail.scrollLeft + rail.clientWidth / 2;
      let active = 0;
      items.forEach((item, index) => { if (Math.abs(item.offsetLeft + item.offsetWidth / 2 - center) < Math.abs(items[active].offsetLeft + items[active].offsetWidth / 2 - center)) active = index; });
      output.textContent = `${active + 1} dari ${items.length}`;
    };
    rail.addEventListener('scroll', update, { passive: true });
    update();
  };
  document.querySelectorAll('[data-youtube-showcase]').forEach((root) => bind(root.querySelector('.youtube-showcase-rail'), root.querySelector('[data-youtube-count]'), ':scope > li:not([hidden])'));
  const sidebar = document.querySelector('.home-juknis-sidebar');
  bind(sidebar, sidebar?.querySelector('[data-sidebar-rail-status]'), ':scope > .module-card, :scope > .instagram-cache');
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



function setupYouTubeShowcase() {
  const idPattern = /^[A-Za-z0-9_-]{11}$/;
  document.querySelectorAll('[data-youtube-showcase]').forEach((root) => {
    const player = root.querySelector('[data-youtube-player]');
    const preview = root.querySelector('[data-youtube-preview]');
    const play = root.querySelector('[data-youtube-play]');
    const title = root.querySelector('[data-youtube-title]');
    const fallback = root.querySelector('[data-youtube-fallback]');
    const source = root.querySelector('[data-youtube-source]');
    const status = root.querySelector('[data-youtube-status]');
    const items = Array.from(root.querySelectorAll('[data-youtube-item]'));
    if (!player || !preview || !play || !title || !fallback || !items.length) return;

    let active = items.find((item) => item.getAttribute('aria-current') === 'true') || items[0];
    let iframe = null;
    const video = (item) => {
      const id = item.dataset.videoId || '';
      if (!idPattern.test(id)) return null;
      return { id, title: item.dataset.videoTitle || '', thumbnail: item.dataset.videoThumbnail || '', source: item.dataset.videoSource || '' };
    };
    const setActive = (item) => {
      const selected = video(item);
      if (!selected) return;
      active = item;
      items.forEach((button) => {
        const current = button === item;
        button.classList.toggle('is-active', current);
        button.setAttribute('aria-current', String(current));
        const state = button.querySelector('.youtube-showcase-item__state');
        if (state) state.textContent = current ? 'Sedang dipilih' : (button.dataset.videoSource === 'wajib' ? 'Pilihan' : 'Terbaru');
      });
      title.textContent = selected.title;
      if (source) source.textContent = selected.source === 'wajib' ? 'Video pilihan' : 'Video terbaru';
      play.setAttribute('aria-label', `Putar video: ${selected.title}`);
      fallback.href = `https://www.youtube.com/watch?v=${selected.id}`;
      if (selected.thumbnail) preview.src = selected.thumbnail;
      if (iframe) {
        iframe.title = `Video YouTube: ${selected.title}`;
        iframe.src = `https://www.youtube-nocookie.com/embed/${selected.id}?autoplay=1&rel=0`;
      }
      if (status) status.textContent = `Video dipilih: ${selected.title}`;
    };
    const playVideo = () => {
      const selected = video(active);
      if (!selected) return;
      if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.setAttribute('title', `Video YouTube: ${selected.title}`);
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        iframe.loading = 'lazy';
        iframe.className = 'youtube-showcase-iframe';
        player.appendChild(iframe);
      }
      iframe.src = `https://www.youtube-nocookie.com/embed/${selected.id}?autoplay=1&rel=0`;
      player.classList.add('is-playing');
      fallback.classList.add('youtube-showcase-fallback-link');
      player.insertAdjacentElement('afterend', fallback);
      preview.hidden = true;
      play.hidden = true;
      if (status) status.textContent = `Memutar video: ${selected.title}`;
    };
    items.forEach((item) => item.addEventListener('click', () => setActive(item)));
    play.addEventListener('click', playVideo);
    setActive(active);
  });
}

function setupEditorialArticleShare() {
  document.querySelectorAll('[data-editorial-share]').forEach((share) => {
    const title = share.dataset.title || document.title;
    const url = share.dataset.url || window.location.href;
    const status = share.querySelector('[data-share-status]');
    const announce = (message) => {
      if (status) status.textContent = message;
    };
    const copy = async () => {
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(url);
        } else {
          const input = document.createElement('textarea');
          input.value = url;
          input.setAttribute('readonly', '');
          input.style.position = 'fixed';
          input.style.opacity = '0';
          document.body.appendChild(input);
          input.select();
          if (!document.execCommand('copy')) throw new Error('copy failed');
          input.remove();
        }
        announce('Tautan berhasil disalin.');
      } catch (error) {
        announce('Tautan tidak dapat disalin.');
      }
    };
    share.querySelector('[data-share-copy]')?.addEventListener('click', copy);
    const nativeButton = share.querySelector('[data-share-native]');
    if (!nativeButton) return;
    if (!navigator.share) {
      nativeButton.hidden = true;
      return;
    }
    nativeButton.addEventListener('click', async () => {
      try {
        await navigator.share({ title, url });
        announce('Artikel berhasil dibagikan.');
      } catch (error) {
        if (error?.name !== 'AbortError') announce('Artikel tidak dapat dibagikan.');
      }
    });
  });
}
