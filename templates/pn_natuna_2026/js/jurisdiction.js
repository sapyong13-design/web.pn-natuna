(() => {
  'use strict';
  const atlas = document.querySelector('.pn-atlas');
  if (!atlas) return;

  const links = [...atlas.querySelectorAll('[data-atlas-region]')];
  const panels = [...atlas.querySelectorAll('[data-atlas-panel]')];
  const groups = [...atlas.querySelectorAll('[data-atlas-group]')];
  const controls = atlas.querySelector('.atlas-controls');
  const select = controls.querySelector('[data-atlas-select]');
  const status = controls.querySelector('[data-atlas-selection]');
  const points = [...atlas.querySelectorAll('[data-atlas-point]')];
  const label = atlas.querySelector('[data-atlas-map-label]');
  let region = '';

  function update(next, district = '', village = '') {
    const changeRegion = region !== next;
    region = next;
    select.value = district;
    links.forEach(link => {
      if (link.dataset.atlasRegion === next) link.setAttribute('aria-current', 'true');
      else link.removeAttribute('aria-current');
    });
    panels.forEach(panel => { panel.hidden = panel.dataset.atlasPanel !== next; });
    groups.forEach(group => {
      group.hidden = (next !== 'overview' && group.dataset.region !== next) || Boolean(district && group.dataset.atlasGroup !== district);
      if (changeRegion || district) group.open = !group.hidden && Boolean(district);
    });
    points.forEach(point => {
      point.classList.toggle('is-muted', Boolean(district && point.dataset.atlasDistrict !== district));
      point.classList.toggle('is-selected', Boolean(village && point.dataset.atlasDistrict === district && point.dataset.atlasPoint === village));
    });
    const activePanel = panels.find(panel => panel.dataset.atlasPanel === next);
    const shown = groups.filter(group => !group.hidden);
    const count = shown.reduce((n, group) => n + group.querySelectorAll('[data-atlas-village]').length, 0);
    const pinned = (next === 'overview' ? atlas : activePanel).querySelectorAll('[data-atlas-point]:not(.is-muted)').length;
    const detail = count === pinned ? `${count} lokasi pada peta` : `${pinned} dari ${count} lokasi pada peta`;
    status.textContent = next === 'overview' ? `${count} desa/kelurahan · ${pinned} bertitik pada peta detail` : district ? `${district} · ${detail}` : `${shown.length} kecamatan · ${detail}`;
    label.textContent = village ? `${village} · ${district}` : district && !pinned ? 'Kecamatan ini belum memiliki titik pada peta.' : '';
    label.hidden = !label.textContent;
    atlas.querySelectorAll('[data-atlas-locate]').forEach(button => {
      button.setAttribute('aria-pressed', String(Boolean(village && button.closest('[data-atlas-group]').dataset.atlasGroup === district && button.textContent.trim() === village)));
    });
  }

  groups.forEach(group => {
    group.querySelectorAll('[data-mapped="yes"] > span').forEach(location => {
      const button = document.createElement('button');
      button.type = 'button';
      button.dataset.atlasLocate = '';
      button.textContent = location.textContent;
      button.setAttribute('aria-pressed', 'false');
      button.addEventListener('click', () => update(group.dataset.region, group.dataset.atlasGroup, button.textContent));
      location.replaceWith(button);
    });
  });
  links.forEach(link => link.addEventListener('click', event => {
    if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    update(link.dataset.atlasRegion);
  }));
  atlas.querySelectorAll('[data-atlas-marker]').forEach(marker => marker.addEventListener('click', () => update(marker.dataset.atlasMarker)));
  select.addEventListener('change', () => update(select.selectedOptions[0].dataset.region || region, select.value));
  points.forEach(point => point.addEventListener('click', () => update(region, point.dataset.atlasDistrict, point.dataset.atlasPoint)));
  controls.hidden = false;
  atlas.classList.add('is-ready');
  update(panels.find(panel => `#${panel.id}` === location.hash)?.dataset.atlasPanel || links[0].dataset.atlasRegion);
})();
