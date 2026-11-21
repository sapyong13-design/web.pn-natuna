(() => {
  'use strict';
  const root = document.querySelector('.pn-org');
  if (!root) return;
  const find = key => root.querySelector(`[data-org-${key}]`);
  const all = key => Array.from(root.querySelectorAll(`[data-org-${key}]`));
  const people = JSON.parse(find('data').content.textContent);
  const preview = find('preview'), search = find('search'), status = find('status');
  const photo = find('photo'), link = find('link'), note = find('note'), closeButton = find('close');
  const branches = all('branch'), units = all('unit'), names = all('person');
  const normalize = text => text.toLocaleLowerCase('id').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, ' ').trim();
  const rows = all('entry').map(entry => {
    const trigger = entry.matches('[data-org-person]') ? entry : entry.querySelector('[data-org-person]');
    const unit = entry.closest('[data-org-unit]'), branch = entry.closest('[data-org-branch]');
    const id = trigger?.dataset.orgPerson;
    const role = trigger?.dataset.orgRole || entry.dataset.orgRole || '';
    const context = unit?.dataset.orgUnit || branch?.dataset.orgBranchLabel || '';
    return {entry, trigger, unit, branch, id, role, context, keywords: normalize([people[id]?.name, role, context, branch?.dataset.orgBranchLabel, entry.textContent].filter(Boolean).join(' '))};
  });
  const byTrigger = new Map(rows.filter(row => row.trigger).map(row => [row.trigger, row]));
  const compact = matchMedia('(max-width: 720px)');
  let active = null, pinned = false, hideTimer, ignoreFocus = false, positionFrame = 0;
  const branchState = new Map();
  let filtering = false;

  function position() {
    if (!active || preview.hidden) return;
    if (compact.matches) { preview.style.removeProperty('left'); preview.style.removeProperty('top'); return; }
    const r = active.trigger.getBoundingClientRect(), box = preview.getBoundingClientRect(), gap = 8, pad = 12;
    let x = r.right + gap, y = r.top;
    if (x + box.width > document.documentElement.clientWidth - pad) x = r.left - box.width - gap;
    if (x < pad) { x = r.left; y = r.bottom + gap; if (y + box.height > innerHeight - pad) y = r.top - box.height - gap; }
    preview.style.left = `${Math.max(pad, Math.min(x, document.documentElement.clientWidth - box.width - pad))}px`;
    preview.style.top = `${Math.max(pad, Math.min(y, innerHeight - box.height - pad))}px`;
  }
  function queuePosition() {
    if (!active || positionFrame) return;
    positionFrame = requestAnimationFrame(() => { positionFrame = 0; position(); });
  }
  function cancelHide() { clearTimeout(hideTimer); }
  function close(restore = false) {
    if (!active) return;
    const previous = active.trigger;
    cancelHide();
    active.trigger.setAttribute('aria-expanded', 'false');
    root.querySelectorAll('.is-active').forEach(el => el.classList.remove('is-active'));
    active = null; pinned = false; preview.hidden = true;
    if (restore) { ignoreFocus = true; previous.focus({preventScroll:true}); ignoreFocus = false; }
  }
  function deferClose() {
    cancelHide();
    if (!pinned) hideTimer = setTimeout(() => { if (!preview.matches(':hover') && !preview.contains(document.activeElement)) close(); }, 230);
  }
  function open(trigger, pin = false) {
    const row = byTrigger.get(trigger), person = row && people[row.id];
    if (!person) return;
    cancelHide();
    if (active !== row) {
      close(); active = row;
      for (const item of rows) {
        if (item.id !== row.id) continue;
        item.trigger.classList.add('is-active');
        item.unit?.classList.add('is-active');
        item.branch?.classList.add('is-active');
      }
      find('name').textContent = person.name;
      find('context').textContent = [...new Set([row.role, row.context].filter(Boolean))].join(' · ');
      const roles = [...new Set(rows.filter(other => other.id === row.id).map(other => [...new Set([other.role,other.context].filter(Boolean))].join(' · ')))];
      find('roles').replaceChildren(...roles.map(label => { const li = document.createElement('li'); li.textContent = label; return li; }));
      if (note) { note.textContent = person.note || ''; note.hidden = !person.note; }
      photo.hidden = !person.photo;
      if (person.photo) photo.src = person.photo; else photo.removeAttribute('src');
      link.hidden = !person.url;
      if (person.url) link.href = person.url; else link.removeAttribute('href');
      trigger.setAttribute('aria-expanded','true'); preview.hidden = false;
    }
    pinned = pinned || pin;
    position();
    if (pin) (link.hidden ? closeButton : link).focus({preventScroll:true});
  }
  names.forEach(trigger => {
    trigger.setAttribute('aria-haspopup','dialog'); trigger.setAttribute('aria-controls',preview.id); trigger.setAttribute('aria-expanded','false');
    trigger.addEventListener('pointerenter', e => { if (e.pointerType !== 'touch' && !compact.matches && !pinned) open(trigger); });
    trigger.addEventListener('pointerleave', () => { if (document.activeElement !== trigger) deferClose(); });
    trigger.addEventListener('focus', () => { if (!ignoreFocus && !compact.matches) open(trigger); });
    trigger.addEventListener('blur', e => { if (!preview.contains(e.relatedTarget) && !pinned) deferClose(); });
    trigger.addEventListener('click', e => {
      if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) return;
      e.preventDefault(); open(trigger,true);
    });
  });
  closeButton.addEventListener('click', () => close(true));
  preview.addEventListener('pointerenter', cancelHide);
  preview.addEventListener('pointerleave', deferClose);
  preview.addEventListener('focusin', cancelHide);
  preview.addEventListener('focusout', e => { if (!preview.contains(e.relatedTarget) && active && e.relatedTarget !== active.trigger) close(); });
  photo.addEventListener('load', queuePosition);
  photo.addEventListener('error', () => { photo.hidden = true; queuePosition(); });
  document.addEventListener('pointerdown', e => { if (active && !preview.contains(e.target) && !e.target.closest('[data-org-person]')) close(); });
  document.addEventListener('keydown', e => {
    if (active && e.key === 'Escape') { e.preventDefault(); close(preview.contains(document.activeElement) || active.trigger === document.activeElement); }
    else if (active && e.key === 'Tab' && pinned && !e.shiftKey && document.activeElement === link) {
      // Continue in chart reading order after the pinned card instead of skipping to the footer.
      close(true);
    }
  });
  window.addEventListener('resize',queuePosition);
  window.addEventListener('scroll',queuePosition,{passive:true});

  root.querySelectorAll('.org-portrait').forEach(img => {
    img.addEventListener('error', () => { img.hidden = true; });
    if (img.complete && !img.naturalWidth) img.hidden = true;
  });
  const groups = [...root.querySelectorAll('details')];
  if (compact.matches) groups.filter(el => el.hasAttribute('data-org-branch')).forEach(el => { el.open = false; });
  const vacancyToggle = find('vacancy-filter');
  find('vacancy-count').textContent = all('vacant').length;
  function filter() {
    close();
    const vacantOnly = vacancyToggle.getAttribute('aria-pressed') === 'true';
    root.classList.toggle('is-vacancy-filtered', vacantOnly);
    const words = normalize(search.value).split(' ').filter(Boolean), hasQuery = words.length > 0 || vacantOnly;
    if (hasQuery && !filtering) for (const group of groups) branchState.set(group, group.open);
    const shownUnits = new Set(), shownBranches = new Set(), shownPeople = new Set();
    let count = 0;
    for (const row of rows) {
      const match = (!vacantOnly || !row.id) && words.every(word => row.keywords.includes(word));
      row.entry.hidden = !match; row.entry.classList.toggle('is-match', hasQuery && match);
      if (!match) continue;
      count++; if (row.id) shownPeople.add(row.id); shownUnits.add(row.unit); shownBranches.add(row.branch);
    }
    for (const unit of units) unit.hidden = hasQuery && !shownUnits.has(unit);
    for (const branch of branches) branch.hidden = hasQuery && !shownBranches.has(branch);
    if (hasQuery) groups.forEach(group => { if (!group.hidden) group.open = true; });
    else if (filtering) { for (const [group, wasOpen] of branchState) group.open = wasOpen; branchState.clear(); }
    filtering = hasQuery;
    if (find('empty')) find('empty').hidden = !hasQuery || count > 0;
    if (find('clear')) find('clear').hidden = !search.value;
    status.textContent = !hasQuery ? defaultStatus : !count ? 'Tidak ada hasil.' : vacantOnly ? `${count} jabatan kosong ditampilkan` : `${shownPeople.size} orang · ${count} posisi cocok`;
  }
  const defaultStatus = status.textContent;
  vacancyToggle.addEventListener('click', () => {
    vacancyToggle.setAttribute('aria-pressed', String(vacancyToggle.getAttribute('aria-pressed') !== 'true'));
    filter();
  });
  search.addEventListener('input',filter);
  find('clear')?.addEventListener('click',() => {search.value = ''; filter(); search.focus();});
  root.classList.add('is-ready');
})();
