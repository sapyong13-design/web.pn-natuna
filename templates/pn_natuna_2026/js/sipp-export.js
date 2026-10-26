(() => {
  'use strict';

  const stage = document.getElementById('sipp-stage');
  const poster = document.getElementById('sipp-poster');
  if (!stage || !poster) return;

  const triggers = Array.from(document.querySelectorAll('[data-sipp-export]'));
  if (!triggers.length) return;

  const listEl = document.getElementById('sipp-poster-list');
  const dateEl = document.getElementById('sipp-poster-date');
  const dayEl = document.getElementById('sipp-poster-day');
  const weekdayEl = document.getElementById('sipp-poster-weekday');
  const monthEl = document.getElementById('sipp-poster-month');
  const yearEl = document.getElementById('sipp-poster-year');
  const metaEl = document.getElementById('sipp-poster-meta');
  const noteEl = document.getElementById('sipp-poster-note');
  const updatedEl = document.getElementById('sipp-poster-updated');
  const pngBtn = stage.querySelector('[data-sipp-stage-png]');
  const videoBtn = stage.querySelector('[data-sipp-stage-video]');
  const themeBtns = Array.from(stage.querySelectorAll('[data-sipp-theme]'));
  const printBtn = stage.querySelector('[data-sipp-stage-print]');
  const fullBtn = stage.querySelector('[data-sipp-stage-fullscreen]');
  const closeBtn = stage.querySelector('[data-sipp-stage-close]');

  // Dialog dipindah ke body: keluar dari overflow board dan bisa dicetak
  // tanpa menyembunyikan poster.
  document.body.appendChild(stage);
  poster.dataset.theme = poster.dataset.theme || 'maroon';

  const MONTHS = {
    jan: 'Januari', januari: 'Januari',
    feb: 'Februari', februari: 'Februari',
    mar: 'Maret', maret: 'Maret',
    apr: 'April', april: 'April',
    mei: 'Mei',
    jun: 'Juni', juni: 'Juni',
    jul: 'Juli', juli: 'Juli',
    agu: 'Agustus', ags: 'Agustus', agt: 'Agustus', agustus: 'Agustus',
    sep: 'September', september: 'September',
    okt: 'Oktober', oktober: 'Oktober',
    nov: 'November', november: 'November',
    des: 'Desember', desember: 'Desember'
  };

  // SIPP menulis "Kamis, 22 Okt. 2026"; poster memakai nama bulan penuh.
  const fullDateLabel = (label) => {
    const match = /^\s*(?:([^,]+),\s*)?(\d{1,2})\s+([A-Za-z]+)\.?\s+(\d{4})\s*$/.exec(label || '');
    if (!match) return (label || '').trim();
    const weekday = (match[1] || '').trim();
    const month = MONTHS[(match[3] || '').toLowerCase()] || match[3];
    return `${weekday ? weekday + ', ' : ''}${Number(match[2])} ${month} ${match[4]}`.trim();
  };

  // "Sabtu, 24 Oktober 2026" -> bagian-bagian untuk tanggal raksasa.
  const splitDate = (label) => {
    const m = /^(?:([^,]+),\s*)?(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/.exec((label || '').trim());
    if (!m) return { weekday: '', day: '', month: '', year: '' };
    return { weekday: (m[1] || '').trim(), day: m[2], month: m[3], year: m[4] };
  };

  const readRows = (panel) => Array.from(panel.querySelectorAll('.sipp-cards .sipp-card')).map((card) => ({
    no: card.dataset.no || '',
    key: card.dataset.caseCategory || 'other',
    case: card.dataset.case || '',
    room: card.dataset.room || '',
    agenda: card.dataset.agenda || '',
    circuit: card.dataset.circuit === '1'
  }));

  // "Rabu, 21 Okt. 2026 07:35:35 WIB" -> "Rabu, 21 Oktober 2026 07:35:35 WIB"
  const expandMonthInText = (text) => text.replace(
    /(\d{1,2})\s+([A-Za-z]+)\.?\s+(\d{4})/,
    (whole, day, month, year) => {
      const full = MONTHS[month.toLowerCase()];
      return full ? `${Number(day)} ${full} ${year}` : whole;
    }
  );

  const readUpdated = (panel) => {
    const spans = Array.from(panel.querySelectorAll('.sipp-day-toolbar__meta span'));
    const hit = spans.find((span) => /^Diperbarui/i.test(span.textContent.trim()));
    return hit ? hit.textContent.trim().replace(/^Diperbarui\s*/i, '') : '';
  };

  let opener = null;
  let currentRows = [];
  let currentDate = '';
  let currentKey = 'today';
  // Efek tambahan (cover, progress bar, kilau kategori) bisa dimatikan:
  // versi sederhana lebih lancar di mesin/encoder yang lambat.
  let fullEffects = false;

  const buildRow = (row, index, dense) => {
    const li = document.createElement('li');
    li.className = `sipp-poster__row sipp-poster__row--${row.key}`;
    li.style.setProperty('--i', String(Math.min(index, 24)));

    const no = document.createElement('span');
    no.className = 'sipp-poster__no';
    no.textContent = String(row.no || index + 1).padStart(2, '0');
    no.setAttribute('aria-hidden', 'true');

    const body = document.createElement('span');
    body.className = 'sipp-poster__body';

    const caseEl = document.createElement('span');
    caseEl.className = 'sipp-poster__case';
    caseEl.textContent = row.case;
    body.appendChild(caseEl);

    const roomParts = [row.room].filter(Boolean);
    if (row.circuit) roomParts.push('Sidang Keliling');

    const addLine = (className, text) => {
      if (!text) return;
      const line = document.createElement('span');
      line.className = className;
      line.textContent = text;
      body.appendChild(line);
    };

    if (dense) {
      // Hari padat: ruang dan agenda dirapatkan jadi satu baris.
      addLine('sipp-poster__line', [...roomParts, row.agenda].filter(Boolean).join(' · '));
    } else {
      addLine('sipp-poster__line', roomParts.join(' · '));
      addLine('sipp-poster__agenda', row.agenda);
    }

    li.appendChild(no);
    li.appendChild(body);
    return li;
  };

  // Poster bertinggi tetap (rasio 4:5), jadi tipografi dikecilkan bertahap
  // sampai seluruh perkara hari itu muat tanpa gulir.
  const fitPoster = () => {
    poster.style.removeProperty('--fit');
    const available = listEl.clientHeight;
    if (!available) return;
    let scale = 1;
    for (let i = 0; i < 8; i += 1) {
      const needed = listEl.scrollHeight;
      if (needed <= available + 1) break;
      scale *= Math.max(0.86, available / needed);
      poster.style.setProperty('--fit', scale.toFixed(3));
    }
  };

  const fill = (panel, key) => {
    currentRows = readRows(panel);
    currentKey = key;
    const tab = document.getElementById(`sipp-tab-${key}`);
    currentDate = fullDateLabel((tab?.querySelector('span')?.textContent || '').trim());

    const rooms = new Set(currentRows.map((row) => row.room).filter(Boolean));
    dateEl.textContent = currentDate;
    const parts = splitDate(currentDate);
    if (dayEl) dayEl.textContent = parts.day;
    if (weekdayEl) weekdayEl.textContent = parts.weekday;
    if (monthEl) monthEl.textContent = parts.month;
    if (yearEl) yearEl.textContent = parts.year;

    // Badge konteks: di feed yang di-scroll cepat orang tidak membaca tanggal,
    // jadi "BESOK" dan jumlah perkara ditulis eksplisit.
    const when = key === 'tomorrow' ? 'Besok' : 'Hari ini';
    const badge = [when, currentRows.length ? `${currentRows.length} perkara` : 'Tidak ada sidang'];
    if (rooms.size > 1) badge.push(`${rooms.size} ruang`);
    metaEl.textContent = badge.join(' · ');

    const updated = readUpdated(panel);
    updatedEl.textContent = updated ? `Data per ${expandMonthInText(updated)}` : '';

    // Kepadatan ditentukan lebih dulu karena memengaruhi jumlah baris per kartu.
    const total = currentRows.length;
    const density = total > 8 ? 'dense' : total > 6 ? 'compact' : total <= 4 ? 'airy' : 'normal';
    poster.dataset.density = density;

    // Hari dengan sedikit perkara menyisakan ruang; isi dengan catatan resmi
    // supaya poster tidak terlihat kosong.
    noteEl.hidden = true;

    listEl.textContent = '';
    listEl.classList.toggle('sipp-poster__list--cols', currentRows.length > MAX_CARDS);
    const shownRows = currentRows.length > MAX_GRID ? currentRows.slice(0, MAX_GRID - 1) : currentRows;
    shownRows.forEach((row, index) => listEl.appendChild(buildRow(row, index, density === 'dense')));
    const overflowRows = currentRows.length - shownRows.length;
    if (overflowRows > 0) {
      const more = document.createElement('li');
      more.className = 'sipp-poster__more';
      const strong = document.createElement('strong');
      strong.textContent = `dan ${overflowRows} perkara lainnya`;
      const small = document.createElement('span');
      small.textContent = 'Daftar lengkap: sipp.pn-natuna.go.id';
      more.append(strong, small);
      listEl.appendChild(more);
    }

    if (!currentRows.length) {
      // Varian hari kosong: ritual harian tetap jalan walau tidak ada perkara.
      const empty = document.createElement('li');
      empty.className = 'sipp-poster__empty';
      const title = document.createElement('strong');
      title.textContent = currentKey === 'tomorrow' ? 'Tidak ada sidang besok' : 'Tidak ada sidang hari ini';
      const sub = document.createElement('span');
      sub.textContent = 'Layanan pengadilan tetap buka Senin–Jumat, 08.00–16.00 WIB.';
      const link = document.createElement('span');
      link.className = 'sipp-poster__empty-link';
      link.textContent = 'Jadwal terbaru: sipp.pn-natuna.go.id';
      empty.append(title, sub, link);
      listEl.appendChild(empty);
    }

    poster.dataset.count = String(currentRows.length);
  };

  const open = (key, trigger) => {
    const panel = document.getElementById(`sipp-panel-${key}`);
    if (!panel) return;

    opener = trigger;
    fill(panel, key);
    stage.hidden = false;
    document.body.classList.add('sipp-stage-open');
    requestAnimationFrame(() => {
      stage.classList.add('is-open');
      // Diukur setelah poster terlihat: saat masih hidden clientHeight = 0.
      fitPoster();
      document.fonts?.ready.then(fitPoster).catch(() => {});
    });
    closeBtn.focus();
  };

  const close = () => {
    if (stage.hidden) return;
    stage.classList.remove('is-open');
    document.body.classList.remove('sipp-stage-open');
    if (document.fullscreenElement) document.exitFullscreen?.().catch(() => {});
    window.setTimeout(() => {
      stage.hidden = true;
      listEl.textContent = '';
    }, 220);
    opener?.focus();
  };

  const SERIF = '"Fraunces", Georgia, "Times New Roman", serif';
  const SANS = '"Inter", "Segoe UI", system-ui, sans-serif';

  const ROW_COLORS = {
    criminal: { accent: '#c0392b', ink: '#ffffff', glow: 'rgba(255, 92, 70, 0.95)', neon: '#ff5a45' },
    civil: { accent: '#1f7a52', ink: '#ffffff', glow: 'rgba(64, 226, 150, 0.9)', neon: '#40e296' },
    fishery: { accent: '#3aa6c9', ink: '#08252e', glow: 'rgba(120, 224, 255, 0.95)', neon: '#78e0ff' },
    execution: { accent: '#2c6da4', ink: '#ffffff', glow: 'rgba(96, 176, 245, 0.9)', neon: '#60b0f5' },
    'special-civil': { accent: '#2c6da4', ink: '#ffffff', glow: 'rgba(96, 176, 245, 0.9)', neon: '#60b0f5' },
    other: { accent: '#b98f24', ink: '#2f140e', glow: 'rgba(240, 214, 140, 0.8)', neon: '#f0d68c' }
  };

  const fitText = (ctx, text, maxWidth) => {
    if (ctx.measureText(text).width <= maxWidth) return text;
    let cut = text;
    while (cut.length > 4 && ctx.measureText(`${cut}…`).width > maxWidth) {
      cut = cut.slice(0, -1);
    }
    return `${cut.trim()}…`;
  };

  const roundRect = (ctx, x, y, w, h, r) => {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  };

  const loadImage = (src) => new Promise((resolve) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = () => resolve(null);
    img.src = src;
  });

  let PN_LOGO = null;
  let ASN_LOGO = null;
  let ASN_LOGO_LIGHT = null;
  let QR_IMG = null;
  const ensureLogos = async () => {
    if (!PN_LOGO || !ASN_LOGO || !ASN_LOGO_LIGHT || !QR_IMG) {
      const [pn, asn, asnLight, qr] = await Promise.all([
        loadImage('/images/brand/logo-pn-natuna-mark.webp'),
        loadImage('/images/brand/logo-asn-berakhlak.webp'),
        loadImage('/images/brand/logo-asn-berakhlak-light.webp'),
        loadImage('/images/brand/qr-sipp.png')
      ]);
      PN_LOGO = pn;
      ASN_LOGO = asn;
      ASN_LOGO_LIGHT = asnLight;
      QR_IMG = qr;
    }
  };

  /* ---------- Lukis poster: PNG & video 1080 x 1350 ---------- */

  const W = 1080;
  const H = 1920;
  const PAD = 72;
  const ROW_ANIM = 0.55;
  const HEAD_ANIM = 0.9;
  const MAX_VIDEO = 10;
  // UI Reels/Story menutup ~250 px bawah dan ~120 px kanan; konten dijaga di dalam.
  const SAFE_BOTTOM = 250;
  const SAFE_RIGHT = 96;
  // Story menutup ~180 px atas dengan header profil; geser konten turun.
  const SAFE_TOP = 150;
  const MAX_CARDS = 10;   // satu kolom
  const MAX_GRID = 16;    // dua kolom
  const COVER_HOLD = 0.8;
  const COVER_FADE = 0.25;
  const FOOT_Y = H - SAFE_BOTTOM - 18;

  const THEMES = {
    maroon: {
      bg: ['#2f140e', '#5c1508', '#7d2a14'],
      barTop: 'rgba(234, 216, 167, 0.10)',
      court: '#ead395', sub: 'rgba(244, 227, 189, 0.72)',
      title: '#ffffff', date: '#ead395', meta: 'rgba(244, 227, 189, 0.82)',
      divider: 'rgba(234, 216, 167, 0.35)',
      cardBg: 'rgba(255, 255, 255, 0.06)', cardLine: 'rgba(234, 216, 167, 0.18)',
      caseText: '#ffffff', lineText: 'rgba(244, 227, 189, 0.86)', agendaText: 'rgba(244, 227, 189, 0.95)',
      foot: 'rgba(244, 227, 189, 0.82)'
    },
    navy: {
      bg: ['#07131d', '#0f2735', '#17415a'],
      barTop: 'rgba(240, 201, 135, 0.12)',
      court: '#f0c987', sub: 'rgba(226, 236, 244, 0.72)',
      title: '#ffffff', date: '#f0c987', meta: 'rgba(226, 236, 244, 0.82)',
      divider: 'rgba(240, 201, 135, 0.32)',
      cardBg: 'rgba(255, 255, 255, 0.07)', cardLine: 'rgba(240, 201, 135, 0.18)',
      caseText: '#ffffff', lineText: 'rgba(226, 236, 244, 0.86)', agendaText: 'rgba(226, 236, 244, 0.95)',
      foot: 'rgba(226, 236, 244, 0.82)'
    },
    emerald: {
      bg: ['#06231b', '#0c3a2c', '#145a44'],
      barTop: 'rgba(180, 240, 210, 0.10)',
      court: '#9fe3c4', sub: 'rgba(214, 240, 228, 0.72)',
      title: '#ffffff', date: '#9fe3c4', meta: 'rgba(214, 240, 228, 0.82)',
      divider: 'rgba(159, 227, 196, 0.32)',
      cardBg: 'rgba(255, 255, 255, 0.07)', cardLine: 'rgba(159, 227, 196, 0.18)',
      caseText: '#ffffff', lineText: 'rgba(214, 240, 228, 0.86)', agendaText: 'rgba(214, 240, 228, 0.95)',
      foot: 'rgba(214, 240, 228, 0.82)'
    },
    graphite: {
      bg: ['#111317', '#1c1f24', '#2b3037'],
      barTop: 'rgba(226, 232, 240, 0.10)',
      court: '#d7dce4', sub: 'rgba(226, 232, 240, 0.66)',
      title: '#ffffff', date: '#e2e8f0', meta: 'rgba(226, 232, 240, 0.78)',
      divider: 'rgba(226, 232, 240, 0.28)',
      cardBg: 'rgba(255, 255, 255, 0.06)', cardLine: 'rgba(226, 232, 240, 0.16)',
      caseText: '#ffffff', lineText: 'rgba(226, 232, 240, 0.84)', agendaText: 'rgba(240, 244, 250, 0.94)',
      foot: 'rgba(226, 232, 240, 0.80)'
    },
    royal: {
      bg: ['#0d1030', '#171d4a', '#262f6e'],
      barTop: 'rgba(200, 210, 255, 0.12)',
      court: '#bcc6ff', sub: 'rgba(214, 220, 255, 0.70)',
      title: '#ffffff', date: '#bcc6ff', meta: 'rgba(214, 220, 255, 0.80)',
      divider: 'rgba(188, 198, 255, 0.30)',
      cardBg: 'rgba(255, 255, 255, 0.07)', cardLine: 'rgba(188, 198, 255, 0.18)',
      caseText: '#ffffff', lineText: 'rgba(214, 220, 255, 0.86)', agendaText: 'rgba(226, 232, 255, 0.95)',
      foot: 'rgba(214, 220, 255, 0.80)'
    },
    obsidian: {
      bg: ['#08080a', '#121216', '#1e1e24'],
      barTop: 'rgba(232, 200, 122, 0.12)',
      court: '#e8c87a', sub: 'rgba(238, 232, 220, 0.68)',
      title: '#ffffff', date: '#e8c87a', meta: 'rgba(238, 232, 220, 0.80)',
      divider: 'rgba(232, 200, 122, 0.30)',
      cardBg: 'rgba(255, 255, 255, 0.05)', cardLine: 'rgba(232, 200, 122, 0.16)',
      caseText: '#ffffff', lineText: 'rgba(238, 232, 220, 0.90)', agendaText: 'rgba(248, 244, 236, 0.96)',
      foot: 'rgba(238, 232, 220, 0.80)'
    },
    batik: {
      bg: ['#1b1008', '#2f1d0e', '#452c16'],
      barTop: 'rgba(217, 179, 130, 0.12)',
      court: '#d9b382', sub: 'rgba(240, 226, 208, 0.72)',
      title: '#fff8ec', date: '#d9b382', meta: 'rgba(240, 226, 208, 0.82)',
      divider: 'rgba(217, 179, 130, 0.32)',
      cardBg: 'rgba(255, 246, 232, 0.06)', cardLine: 'rgba(217, 179, 130, 0.20)',
      caseText: '#fff8ec', lineText: 'rgba(240, 226, 208, 0.92)', agendaText: 'rgba(250, 242, 230, 0.96)',
      foot: 'rgba(240, 226, 208, 0.80)'
    },
    cream: {
      bg: ['#fbf6ec', '#f3ead8', '#e9dcc3'],
      barTop: 'rgba(47, 20, 14, 0.08)',
      court: '#7a3d10', sub: 'rgba(47, 20, 14, 0.62)',
      title: '#2f140e', date: '#8a5a12', meta: 'rgba(47, 20, 14, 0.72)',
      divider: 'rgba(47, 20, 14, 0.2)',
      cardBg: 'rgba(255, 255, 255, 0.78)', cardLine: 'rgba(47, 20, 14, 0.12)',
      caseText: '#2f140e', lineText: 'rgba(47, 20, 14, 0.78)', agendaText: 'rgba(47, 20, 14, 0.92)',
      foot: 'rgba(47, 20, 14, 0.72)'
    }
  };

  const easeOut = (p) => 1 - Math.pow(1 - p, 3);
  // Overshoot kecil: kartu "mampir" sedikit sebelum diam, terasa hidup.
  const easeOutBack = (p) => {
    const c = 1.32;
    const q = p - 1;
    return 1 + (c + 1) * q * q * q + c * q * q;
  };
  const clamp01 = (value) => Math.min(1, Math.max(0, value));
  const hexToRgb = (hex) => {
    const h = hex.replace('#', '');
    return [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16)];
  };
  const palette = () => THEMES[poster.dataset.theme] || THEMES.maroon;

  // Kepala poster selalu berakhir di titik yang sama, jadi area kartu tetap.
  const HEAD_DAY_SIZE = 196;
  const BADGE_H = 48;
  const AREA_BOTTOM = FOOT_Y - 96;

  // Hari padat (>=9 kartu) memakai tanggal lebih kecil supaya kartu tetap terbaca.
  const headMetrics = (slots) => {
    const daySize = slots >= 9 ? 148 : HEAD_DAY_SIZE;
    const dayBaseline = (PAD - 4) + SAFE_TOP + 96 + 30 + 30 + daySize * 0.86;
    const badgeY = dayBaseline + (slots >= 9 ? 30 : 40);
    const dividerY = badgeY + BADGE_H + (slots >= 9 ? 24 : 30);
    return { daySize, dayBaseline, badgeY, dividerY, areaTop: dividerY + 30 };
  };
  const CARD_W = W - PAD - SAFE_RIGHT;

  const layoutFor = (count, areaTop) => {
    const dense = count > 8;
    const compact = count > 6 && !dense;
    const airy = count <= 4;

    let caseSize = airy ? 50 : compact ? 34 : 42;
    let lineSize = airy ? 30 : compact ? 22 : 27;
    let agendaSize = airy ? 31 : compact ? 23 : 28;
    let padY = airy ? 26 : compact ? 14 : 20;
    const innerGap = 7;

    // Dense: ruang + agenda jadi satu baris, jadi tingginya dua baris saja.
    const measure = () => Math.round(
      caseSize * 1.18 + (innerGap + lineSize * 1.18)
      + (dense ? 0 : innerGap + agendaSize * 1.18) + padY * 2
    );
    let cardH = measure();

    // Kalau blok kartu melebihi area, kecilkan tipografi sampai muat.
    const gap = 16;
    const available = AREA_BOTTOM - areaTop;
    // Rasio langsung: mengecil 6% per langkah terlalu lambat untuk 9-10 kartu.
    let guard = 0;
    while (count * cardH + (count - 1) * gap > available && guard < 8) {
      const ratio = Math.max(0.55, Math.min(0.97, (available - (count - 1) * gap) / (count * cardH)));
      caseSize *= ratio;
      lineSize *= ratio;
      agendaSize *= ratio;
      padY *= ratio;
      cardH = measure();
      guard += 1;
    }
    return {
      cardH,
      gap,
      caseSize: Math.round(caseSize),
      lineSize: Math.round(lineSize),
      agendaSize: Math.round(agendaSize)
    };
  };

  // Satu kartu digambar terpisah agar bisa dipakai frame statis maupun video.
  const drawCard = (ctx, row, layout, y, pal, dense, pulse = 1) => {
    const x = PAD;
    const w = CARD_W;
    const { cardH, caseSize, lineSize, agendaSize } = layout;
    const tone = ROW_COLORS[row.key] || ROW_COLORS.other;

    ctx.fillStyle = pal.cardBg;
    roundRect(ctx, x, y, w, cardH, 18);
    ctx.fill();
    ctx.strokeStyle = pal.cardLine;
    ctx.lineWidth = 1.5;
    ctx.stroke();

    ctx.fillStyle = tone.accent;
    roundRect(ctx, x, y, 8, cardH, 4);
    ctx.fill();

    const cx = x + 58;
    const cy = y + cardH / 2;
    const r = Math.min(26, cardH * 0.3);
    ctx.save();
    ctx.shadowColor = tone.glow;
    ctx.shadowBlur = 24 + 16 * pulse;
    ctx.fillStyle = tone.accent;
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
    ctx.fillStyle = tone.ink;
    ctx.font = `700 ${Math.round(r * 0.95)}px ${SANS}`;
    ctx.textAlign = 'center';
    ctx.fillText(String(row.no || '').padStart(2, '0'), cx, cy + r * 0.34);
    ctx.textAlign = 'left';

    const textX = x + 108;
    const maxW = w - 140;
    const lines = [{ text: row.case, font: `700 ${caseSize}px ${SANS}`, color: pal.caseText }];
    const roomLine = [row.room, row.circuit ? 'Sidang Keliling' : ''].filter(Boolean).join(' · ');

    if (dense) {
      const merged = [roomLine, row.agenda].filter(Boolean).join(' · ');
      if (merged) lines.push({ text: merged, font: `400 ${lineSize}px ${SANS}`, color: pal.lineText });
    } else {
      if (roomLine) lines.push({ text: roomLine, font: `400 ${lineSize}px ${SANS}`, color: pal.lineText });
      if (row.agenda) lines.push({ text: row.agenda, font: `600 ${agendaSize}px ${SANS}`, color: pal.agendaText });
    }

    const sizes = lines.map((line) => parseInt(line.font.match(/(\d+)px/)[1], 10));
    const totalH = sizes.reduce((sum, size) => sum + size * 1.18, 0);
    let textY = y + (cardH - totalH) / 2;
    const glowOn = pal.caseText !== '#2f140e';
    lines.forEach((line, index) => {
      ctx.font = line.font;
      ctx.fillStyle = line.color;
      textY += sizes[index];
      if (index === 0 && glowOn) {
        const label = fitText(ctx, line.text, maxW);
        // Dua lapis blur: halo lebar + inti terang, lalu teks tajam di atasnya.
        ctx.save();
        ctx.filter = 'blur(18px)';
        ctx.globalAlpha = 0.55 + 0.45 * pulse;
        ctx.fillStyle = tone.neon;
        ctx.fillText(label, textX, textY);
        ctx.filter = 'blur(7px)';
        ctx.globalAlpha = 0.85 + 0.15 * pulse;
        ctx.fillText(label, textX, textY);
        ctx.restore();
        ctx.fillStyle = line.color;
        ctx.fillText(label, textX, textY);
      } else {
        ctx.fillText(fitText(ctx, line.text, maxW), textX, textY);
      }
      textY += sizes[index] * 0.18;
    });
  };

  // Varian hari kosong: tetap ada poster yang layak diposting.
  // Lebih dari 10 perkara: tampilkan 9 + satu kartu ringkasan, jangan perkecil
  // tipografi sampai tak terbaca.
  const visibleRows = () => (currentRows.length > MAX_GRID ? currentRows.slice(0, MAX_GRID - 1) : currentRows);
  const overflowCount = () => Math.max(0, currentRows.length - (MAX_GRID - 1));

  // Hari sangat padat: dua kolom, kartu ringkas, tanpa bitmap cache.
  const drawGridMode = (ctx, pal, t, start, stagger, areaTop, rowsShown, overflow, coverT) => {
    const cols = 2;
    const gapX = 16;
    const gapY = 12;
    const cardW = (CARD_W - gapX) / 2;
    const total = rowsShown.length + (overflow ? 1 : 0);
    const rows = Math.ceil(total / cols);
    const available = AREA_BOTTOM - areaTop;
    const cardH = Math.min(150, Math.max(74, (available - (rows - 1) * gapY) / rows));

    const drawOne = (index, alpha) => {
      const col = index % cols;
      const line = Math.floor(index / cols);
      const x = PAD + col * (cardW + gapX);
      const y = areaTop + line * (cardH + gapY);
      const row = index < rowsShown.length ? rowsShown[index] : null;

      ctx.save();
      ctx.globalAlpha = alpha;
      if (!row) {
        ctx.setLineDash([10, 8]);
        ctx.strokeStyle = pal.divider;
        ctx.lineWidth = 2;
        roundRect(ctx, x, y, cardW, cardH, 14);
        ctx.stroke();
        ctx.setLineDash([]);
        ctx.fillStyle = pal.title;
        ctx.font = `700 24px ${SANS}`;
        ctx.textAlign = 'center';
        ctx.fillText(`+${overflow} lainnya`, x + cardW / 2, y + cardH / 2 + 8);
        ctx.textAlign = 'left';
        ctx.restore();
        return;
      }

      const tone = ROW_COLORS[row.key] || ROW_COLORS.other;
      ctx.fillStyle = pal.cardBg;
      roundRect(ctx, x, y, cardW, cardH, 14);
      ctx.fill();
      ctx.strokeStyle = pal.cardLine;
      ctx.lineWidth = 1.5;
      ctx.stroke();
      ctx.fillStyle = tone.accent;
      roundRect(ctx, x, y, 6, cardH, 3);
      ctx.fill();

      ctx.fillStyle = pal.caseText;
      ctx.font = `700 24px ${SANS}`;
      ctx.fillText(fitText(ctx, row.case, cardW - 36), x + 18, y + 36);

      ctx.fillStyle = pal.lineText;
      ctx.font = `400 17px ${SANS}`;
      const meta = [row.room, row.circuit ? 'Sidang Keliling' : '', row.agenda].filter(Boolean).join(' · ');
      ctx.fillText(fitText(ctx, meta, cardW - 36), x + 18, y + 62);
      ctx.restore();
    };

    for (let i = 0; i < total; i += 1) {
      const raw = t === Infinity ? 1 : clamp01((t - (start + i * stagger)) / ROW_ANIM);
      if (raw <= 0) continue;
      drawOne(i, t === Infinity ? 1 : clamp01(easeOut(raw) * 1.1));
    }
    if (coverT > 0.001) {
      for (let i = 0; i < total; i += 1) drawOne(i, coverT);
    }
  };

  const drawMoreCard = (ctx, layout, y, pal, count) => {
    const w = CARD_W;
    const h = layout.cardH;
    // Kartu isi (bukan garis putus-putus saja) supaya batasnya jelas di atas footer.
    ctx.save();
    ctx.fillStyle = pal.caseText === '#2f140e' ? 'rgba(255, 255, 255, 0.55)' : 'rgba(255, 255, 255, 0.045)';
    roundRect(ctx, PAD, y, w, h, 18);
    ctx.fill();
    ctx.setLineDash([12, 9]);
    ctx.strokeStyle = pal.divider;
    ctx.lineWidth = 2.5;
    roundRect(ctx, PAD, y, w, h, 18);
    ctx.stroke();
    ctx.restore();

    // Satu baris saja: dua baris kecil di kartu pendek terbaca seperti menempel footer.
    ctx.textAlign = 'center';
    ctx.fillStyle = pal.title;
    ctx.font = `700 ${Math.max(22, Math.round(layout.caseSize * 0.74))}px ${SANS}`;
    ctx.fillText(`+ ${count} perkara lainnya · lihat SIPP`, PAD + w / 2, y + h / 2 + Math.round(layout.caseSize * 0.26));
    ctx.textAlign = 'left';
  };

  const drawEmptyState = (ctx, pal, t, start, areaTop) => {
    const p = t === Infinity ? 1 : easeOut(clamp01((t - start) / 0.6));
    const boxW = CARD_W;
    const boxH = 470;
    const boxY = areaTop + Math.max(0, (AREA_BOTTOM - areaTop - boxH) / 2);
    const cx = PAD + boxW / 2;
    ctx.save();
    ctx.globalAlpha = p;
    ctx.translate(0, (1 - p) * 24);

    ctx.fillStyle = pal.cardBg;
    roundRect(ctx, PAD, boxY, boxW, boxH, 24);
    ctx.fill();
    ctx.strokeStyle = pal.cardLine;
    ctx.lineWidth = 2;
    ctx.stroke();

    ctx.save();
    ctx.shadowColor = pal.caseText === '#2f140e' ? 'rgba(47, 20, 14, 0.18)' : 'rgba(0, 0, 0, 0.4)';
    ctx.shadowBlur = 26;
    ctx.fillStyle = pal.caseText === '#2f140e' ? 'rgba(47, 20, 14, 0.05)' : 'rgba(255, 255, 255, 0.07)';
    ctx.beginPath();
    ctx.arc(cx, boxY + 112, 56, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
    ctx.strokeStyle = pal.date;
    ctx.lineWidth = 3;
    ctx.beginPath();
    ctx.arc(cx, boxY + 112, 56, 0, Math.PI * 2);
    ctx.stroke();
    ctx.fillStyle = pal.date;
    ctx.font = `700 46px ${SERIF}`;
    ctx.textAlign = 'center';
    ctx.fillText('—', cx, boxY + 128);

    ctx.fillStyle = pal.title;
    ctx.font = `700 52px ${SERIF}`;
    ctx.fillText(currentKey === 'tomorrow' ? 'Tidak ada sidang besok' : 'Tidak ada sidang hari ini', cx, boxY + 254);

    ctx.fillStyle = pal.meta;
    ctx.font = `400 26px ${SANS}`;
    ctx.fillText(
      fitText(ctx, 'Layanan pengadilan tetap buka Senin–Jumat, 08.00–16.00 WIB.', boxW - 90),
      cx,
      boxY + 306
    );

    ctx.fillStyle = pal.court;
    ctx.font = `600 24px ${SANS}`;
    ctx.fillText('Jadwal terbaru: sipp.pn-natuna.go.id', cx, boxY + 358);
    ctx.textAlign = 'left';
    ctx.restore();
  };

  /* ---------- cache bitmap: 1080x1920 @24fps terlalu berat kalau tiap frame
     menggambar ulang glow + blur ---------- */

  const BG_CACHE = new Map();
  const backgroundLayer = (pal, theme) => {
    if (BG_CACHE.has(theme)) return BG_CACHE.get(theme);
    const cv = document.createElement('canvas');
    cv.width = W;
    cv.height = H;
    const c = cv.getContext('2d');
    const grad = c.createLinearGradient(0, 0, W, H);
    grad.addColorStop(0, pal.bg[0]);
    grad.addColorStop(0.55, pal.bg[1]);
    grad.addColorStop(1, pal.bg[2]);
    c.fillStyle = grad;
    c.fillRect(0, 0, W, H);
    const vig = c.createRadialGradient(W / 2, H * 0.42, H * 0.16, W / 2, H * 0.5, H * 0.8);
    vig.addColorStop(0, 'rgba(0, 0, 0, 0)');
    vig.addColorStop(1, 'rgba(0, 0, 0, 0.26)');
    c.fillStyle = vig;
    c.fillRect(0, 0, W, H);
    // Tenun Natuna: anyaman diagonal halus, tekstur tanpa mengganggu teks.
    c.save();
    c.globalAlpha = pal.caseText === '#2f140e' ? 0.05 : 0.065;
    c.strokeStyle = pal.caseText === '#2f140e' ? '#7a3d10' : '#f4e3bd';
    c.lineWidth = 1.6;
    for (let x = -H; x < W + H; x += 36) {
      c.beginPath();
      c.moveTo(x, 0);
      c.lineTo(x + H * 0.55, H);
      c.stroke();
    }
    for (let x = -H; x < W + H; x += 72) {
      c.beginPath();
      c.moveTo(x, 0);
      c.lineTo(x - H * 0.55, H);
      c.stroke();
    }
    c.restore();

    c.fillStyle = pal.barTop;
    c.fillRect(0, 0, W, 8);
    c.fillStyle = '#b98f24';
    c.fillRect(0, 0, W * 0.34, 8);
    BG_CACHE.set(theme, cv);
    return cv;
  };

  // Zoom halus hanya pada latar: murah, tetap terasa hidup.
  const drawBackground = (ctx, pal, progress) => {
    const layer = backgroundLayer(pal, poster.dataset.theme || 'maroon');
    // Satu siklus penuh: zoom mulai dan berakhir di skala yang sama.
    const zoomPhase = (1 - Math.cos(2 * Math.PI * progress)) / 2;
    const zoom = 1 + 0.02 * zoomPhase;
    ctx.save();
    ctx.translate(W / 2, H / 2);
    ctx.scale(zoom, zoom);
    ctx.translate(-W / 2, -H / 2);
    ctx.drawImage(layer, 0, 0);
    ctx.restore();
  };

  const CARD_CACHE = new Map();
  const cardBitmaps = (row, layout, pal, dense) => {
    const theme = poster.dataset.theme || 'maroon';
    const key = [
      theme, dense ? 1 : 0, layout.cardH,
      row.key, row.case, row.room, row.agenda, row.circuit ? 1 : 0
    ].join('|');
    const hit = CARD_CACHE.get(key);
    if (hit) return hit;

    const render = (blur, glowLevel) => {
      const cv = document.createElement('canvas');
      cv.width = W;
      cv.height = layout.cardH + 8;
      const c = cv.getContext('2d');
      if (blur) c.filter = `blur(${blur}px)`;
      drawCard(c, row, layout, 4, pal, dense, glowLevel);
      return cv;
    };

    const entry = { low: render(0, 0.7), high: render(0, 1), soft: render(4, 0.8) };
    CARD_CACHE.set(key, entry);
    return entry;
  };

  const paintFrame = (ctx, time) => {
    const pal = palette();
    const t = Number.isFinite(time) ? time : Number.POSITIVE_INFINITY;
    const dense = poster.dataset.density === 'dense';

    const shown = visibleRows();
    const overflow = overflowCount();
    const slots = Math.max(shown.length + (overflow ? 1 : 0), 1);
    const metrics = headMetrics(slots);
    const areaTop = metrics.areaTop;

    const totalSeconds = timeline().total;
    const progress = t === Infinity ? 1 : clamp01(t / totalSeconds);
    drawBackground(ctx, pal, progress);


    // Progress bar tipis ala Story.
    if (fullEffects && t !== Infinity) {
      ctx.fillStyle = 'rgba(0, 0, 0, 0.45)';
      ctx.fillRect(0, 0, W, 6);
      ctx.fillStyle = pal.date;
      ctx.fillRect(0, 0, W * clamp01(t / totalSeconds), 6);
    }

    // Frame cover: poster utuh di awal supaya thumbnail Reels tidak setengah jadi.
    const coverT = !fullEffects || t === Infinity ? 0 : clamp01(1 - (t - COVER_HOLD) / COVER_FADE);

    // Denyut glow: neon yang diam terlihat seperti text-shadow biasa.
    const pulse = t === Infinity ? 1 : 0.72 + 0.28 * (0.5 + 0.5 * Math.sin(4 * Math.PI * progress));

    ctx.textBaseline = 'alphabetic';
    ctx.textAlign = 'left';

    const headAlpha = t === Infinity ? 1 : easeOut(clamp01(t / HEAD_ANIM));
    ctx.save();
    ctx.globalAlpha = headAlpha;

    const topY = PAD - 4 + SAFE_TOP;
    const markH = 96;
    let brandX = PAD;
    if (PN_LOGO && PN_LOGO.naturalWidth) {
      const markW = markH * (PN_LOGO.naturalWidth / PN_LOGO.naturalHeight);
      ctx.save();
      ctx.shadowColor = 'rgba(0, 0, 0, 0.45)';
      ctx.shadowBlur = 14;
      ctx.shadowOffsetY = 6;
      ctx.drawImage(PN_LOGO, PAD, topY, markW, markH);
      ctx.restore();
      brandX = PAD + markW + 20;
    }

    let brandMax = W - PAD - brandX;

    // BerAKHLAK tanpa plat putih: versi krem untuk tema gelap, asli untuk krem.
    const asnLogo = pal.caseText === '#2f140e' ? ASN_LOGO : ASN_LOGO_LIGHT;
    if (asnLogo && asnLogo.naturalWidth) {
      const asnH = 76;
      const asnW = asnH * (asnLogo.naturalWidth / asnLogo.naturalHeight);
      const asnX = W - PAD - asnW;
      const asnY = topY + 8;
      ctx.save();
      ctx.shadowColor = 'rgba(0, 0, 0, 0.32)';
      ctx.shadowBlur = 12;
      ctx.drawImage(asnLogo, asnX, asnY, asnW, asnH);
      ctx.restore();
      brandMax = asnX - brandX - 24;
    }

    ctx.fillStyle = pal.court;
    ctx.font = `700 15px ${SANS}`;
    if ('letterSpacing' in ctx) ctx.letterSpacing = '4.2px';
    ctx.fillText(fitText(ctx, 'MAHKAMAH AGUNG RI', brandMax), brandX, topY + 18);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

    ctx.fillStyle = pal.title;
    ctx.font = `700 23px ${SANS}`;
    if ('letterSpacing' in ctx) ctx.letterSpacing = '1.4px';
    ctx.fillText(fitText(ctx, 'PENGADILAN NEGERI NATUNA', brandMax), brandX, topY + 47);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

    ctx.fillStyle = pal.date;
    ctx.font = `600 18px ${SANS}`;
    ctx.fillText(fitText(ctx, 'Kelas II', brandMax), brandX, topY + 73);

    const headTop = topY + markH + 30;
    const parts = splitDate(currentDate);

    // Kicker kecil: identitas rubrik, bukan headline.
    ctx.fillStyle = pal.court;
    ctx.font = `700 23px ${SANS}`;
    if ('letterSpacing' in ctx) ctx.letterSpacing = '5px';
    ctx.fillText(fitText(ctx, 'JADWAL SIDANG', W - PAD * 2), PAD, headTop + 18);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

    // Angka hari jadi headline: inilah yang dicari orang di feed.
    ctx.font = `700 ${metrics.daySize}px ${SERIF}`;
    ctx.fillStyle = pal.title;
    const dayLabel = parts.day || '';
    const dayTarget = Number(dayLabel) || 0;
    // Angka berhitung naik di 0,55 detik pertama; lebar kolom pakai nilai final
    // supaya blok kanan tidak bergoyang.
    const countP = t === Infinity ? 1 : easeOut(clamp01(t / 0.55));
    const dayText = !dayTarget || countP >= 1
      ? dayLabel
      : String(Math.max(0, Math.round(dayTarget * countP)));
    const dayW = dayLabel ? ctx.measureText(dayLabel).width : 0;
    ctx.fillText(dayText, PAD - 8, metrics.dayBaseline);

    // Kolom kanan: hari / bulan / tahun, dasarnya sejajar dengan dasar angka.
    const sideX = PAD + Math.max(dayW, 96) + 34;
    ctx.strokeStyle = pal.divider;
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(sideX - 18, metrics.dayBaseline - 108);
    ctx.lineTo(sideX - 18, metrics.dayBaseline);
    ctx.stroke();

    ctx.fillStyle = pal.date;
    ctx.font = `700 24px ${SANS}`;
    if ('letterSpacing' in ctx) ctx.letterSpacing = '4px';
    ctx.fillText((parts.weekday || '').toUpperCase(), sideX, metrics.dayBaseline - 92);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

    ctx.fillStyle = pal.title;
    ctx.font = `600 54px ${SERIF}`;
    ctx.fillText(fitText(ctx, parts.month || '', W - sideX - PAD), sideX, metrics.dayBaseline - 44);

    ctx.fillStyle = pal.court;
    ctx.font = `600 34px ${SERIF}`;
    ctx.fillText(parts.year || '', sideX, metrics.dayBaseline);

    // Badge konteks: "BESOK · 8 PERKARA · 3 RUANG".
    const badgeText = (metaEl.textContent || '').toUpperCase();
    ctx.font = `700 24px ${SANS}`;
    if ('letterSpacing' in ctx) ctx.letterSpacing = '1.2px';
    const badgeW = Math.min(W - PAD * 2, ctx.measureText(badgeText).width + 56);
    ctx.fillStyle = pal.caseText === '#2f140e' ? 'rgba(47, 20, 14, 0.07)' : 'rgba(255, 255, 255, 0.10)';
    roundRect(ctx, PAD, metrics.badgeY, badgeW, BADGE_H, BADGE_H / 2);
    ctx.fill();
    ctx.strokeStyle = pal.date;
    ctx.lineWidth = 2;
    ctx.stroke();
    ctx.fillStyle = pal.date;
    ctx.fillText(fitText(ctx, badgeText, badgeW - 52), PAD + 26, metrics.badgeY + 32);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';
    ctx.restore();

    const dividerY = metrics.dividerY;
    ctx.save();
    ctx.globalAlpha = headAlpha;
    ctx.strokeStyle = pal.divider;
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(PAD, dividerY);
    ctx.lineTo(W - PAD, dividerY);
    ctx.stroke();
    ctx.restore();

    // Watermark lambang: identitas lembut di belakang kartu.
    if (PN_LOGO && PN_LOGO.naturalWidth) {
      const wmW = 720;
      const wmH = wmW * (PN_LOGO.naturalHeight / PN_LOGO.naturalWidth);
      ctx.save();
      ctx.globalAlpha = 0.055;
      ctx.drawImage(PN_LOGO, W - wmW * 0.62, H * 0.44, wmW, wmH);
      ctx.restore();
    }

    const { start, stagger } = timeline();
    if (slots > MAX_CARDS) {
      drawGridMode(ctx, pal, t, start, stagger, areaTop, shown, overflow, coverT);
    } else {
    const layout = layoutFor(slots, areaTop);
    const blockH = slots * layout.cardH + Math.max(0, slots - 1) * layout.gap;
    // Catatan "jadwal dapat berubah" sengaja tidak dicetak di poster ekspor.
    const usableBottom = AREA_BOTTOM;
    const blockTop = areaTop + Math.max(0, (usableBottom - areaTop - blockH) / 2);
    shown.forEach((row, index) => {
      const raw = t === Infinity ? 1 : clamp01((t - (start + index * stagger)) / ROW_ANIM);
      if (raw <= 0) return;
      const spring = t === Infinity ? 1 : easeOutBack(raw);
      const alpha = t === Infinity ? 1 : clamp01(easeOut(raw) * 1.12);
      const y = blockTop + index * (layout.cardH + layout.gap);
      const bmp = cardBitmaps(row, layout, pal, dense);
      const glowMix = t === Infinity ? 1 : clamp01((pulse - 0.72) / 0.28);

      ctx.save();
      ctx.globalAlpha = alpha;
      ctx.translate(0, (1 - Math.min(spring, 1)) * 34);
      if (Math.abs(spring - 1) > 0.002) {
        const midY = y + layout.cardH / 2;
        ctx.translate(0, midY);
        ctx.scale(0.94 + 0.06 * spring, 0.94 + 0.06 * spring);
        ctx.translate(0, -midY);
      }
      // Kilau kategori: satu kotak solid yang memudar, tanpa clip/gradient.
      if (fullEffects && raw < 1) {
        const tone = ROW_COLORS[row.key] || ROW_COLORS.other;
        const [cr, cg, cb] = hexToRgb(tone.accent);
        ctx.save();
        ctx.globalAlpha = alpha * (1 - raw) * 0.35;
        ctx.fillStyle = `rgb(${cr}, ${cg}, ${cb})`;
        roundRect(ctx, PAD, y, CARD_W, layout.cardH, 18);
        ctx.fill();
        ctx.restore();
      }

      // Blur-in: bitmap buram memudar seiring kartu menajam.
      if (raw < 1) {
        ctx.globalAlpha = alpha * (1 - raw) * 0.85;
        ctx.drawImage(bmp.soft, 0, y - 4);
      }
      // Denyut glow: campur dua bitmap, bukan menggambar ulang tiap frame.
      if (glowMix < 0.999) {
        ctx.globalAlpha = alpha * (1 - glowMix);
        ctx.drawImage(bmp.low, 0, y - 4);
      }
      ctx.globalAlpha = alpha * (glowMix < 0.999 ? glowMix : 1);
      ctx.drawImage(bmp.high, 0, y - 4);
      ctx.restore();
    });

    if (overflow) {
      const index = shown.length;
      const raw = t === Infinity ? 1 : clamp01((t - (start + index * stagger)) / ROW_ANIM);
      if (raw > 0) {
        const p = t === Infinity ? 1 : easeOut(raw);
        ctx.save();
        ctx.globalAlpha = p;
        ctx.translate(0, (1 - p) * 26);
        drawMoreCard(ctx, layout, blockTop + index * (layout.cardH + layout.gap), pal, overflow);
        ctx.restore();
      }
    }

    // Cover: poster utuh selama COVER_HOLD, lalu memudar sebelum reveal.
    if (coverT > 0.001) {
      shown.forEach((row, index) => {
        const bmp = cardBitmaps(row, layout, pal, dense);
        ctx.save();
        ctx.globalAlpha = coverT;
        ctx.drawImage(bmp.high, 0, blockTop + index * (layout.cardH + layout.gap) - 4);
        ctx.restore();
      });
      if (overflow) {
        ctx.save();
        ctx.globalAlpha = coverT;
        drawMoreCard(ctx, layout, blockTop + shown.length * (layout.cardH + layout.gap), pal, overflow);
        ctx.restore();
      }
    }

    }

    if (!currentRows.length) drawEmptyState(ctx, pal, t, start, areaTop);

    const footP = t === Infinity ? 1 : easeOut(clamp01((t - start) / 0.6));
    ctx.save();
    ctx.globalAlpha = footP;
    ctx.fillStyle = pal.foot;
    ctx.font = `400 22px ${SANS}`;
    // Jam pembaruan dipangkas dan label "Data per" tidak diduplikasi.
    const compactUpdated = (updatedEl.textContent || '')
      .replace(/^Data per\s*/i, '')
      .replace(/\s*\d{1,2}[:.]\d{2}([:.]\d{2})?\s*WIB\s*$/i, '')
      .trim();
    // Tanggal ringkas: "Minggu, 25 Oktober 2026" -> "25 Okt 2026" agar muat
    // satu baris bersama QR dan handle.
    const shortDate = compactUpdated
      .replace(/^[A-Za-z]+,\s*/, '')
      .replace(/\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+/i,
        (m, bulan) => ` ${bulan.slice(0, 3)} `);
    const footerText = shortDate
      ? `SIPP PN Natuna · diperbarui ${shortDate}`
      : 'Sumber: SIPP PN Natuna';
    ctx.font = `400 21px ${SANS}`;
    ctx.fillText(fitText(ctx, footerText, CARD_W - 380), PAD, FOOT_Y);

    // QR SIPP + handle Instagram: dua jalur menemukan jadwal lengkap.
    const qrSize = 88;
    const qrX = W - PAD - qrSize;
    const qrY = FOOT_Y - qrSize + 6;
    if (QR_IMG && QR_IMG.naturalWidth) {
      ctx.save();
      ctx.globalAlpha = footP;
      ctx.fillStyle = 'rgba(255, 255, 255, 0.95)';
      roundRect(ctx, qrX - 7, qrY - 7, qrSize + 14, qrSize + 14, 10);
      ctx.fill();
      ctx.drawImage(QR_IMG, qrX, qrY, qrSize, qrSize);
      ctx.restore();
    }

    ctx.textAlign = 'right';
    ctx.fillStyle = pal.court;
    ctx.font = `700 23px ${SANS}`;
    ctx.fillText('@pn.natuna', qrX - 22, FOOT_Y - 24);
    ctx.fillStyle = pal.foot;
    ctx.font = `500 20px ${SANS}`;
    ctx.fillText('pn-natuna.go.id', qrX - 22, FOOT_Y + 2);
    ctx.textAlign = 'left';
    ctx.restore();

    // Sapuan cahaya diagonal: latar tidak terasa mati sepanjang video.
    if (fullEffects && t !== Infinity) {
      const cycle = 1.6;
      const sweep = (t % cycle) / cycle;
      const x = -520 + sweep * (W + 1040);
      const band = ctx.createLinearGradient(x, 0, x + 460, H * 0.55);
      const strength = pal.caseText === '#2f140e' ? 0.16 : 0.10;
      band.addColorStop(0, 'rgba(255, 255, 255, 0)');
      band.addColorStop(0.5, `rgba(255, 244, 214, ${strength})`);
      band.addColorStop(1, 'rgba(255, 255, 255, 0)');
      ctx.save();
      ctx.globalCompositeOperation = 'lighter';
      ctx.fillStyle = band;
      ctx.fillRect(0, 0, W, H);
      ctx.restore();
    }
  };

  // Ritme: intro 1,2 s untuk membaca tanggal + badge, tiap kartu 0,55 s,
  // lalu tahan ~1,4 s agar kartu terakhir sempat dibaca. Maksimal 10 detik.
  const timeline = () => {
    const shown = visibleRows();
    const count = Math.max(shown.length + (overflowCount() ? 1 : 0), 1);
    const start = fullEffects ? 1.2 : 0.9;
    const allIn = 8.2; // seluruh kartu selesai muncul
    const stagger = count > 1
      ? Math.min(0.85, Math.max(0.42, (allIn - start - ROW_ANIM) / (count - 1)))
      : 0;
    const lastEnd = start + (count - 1) * stagger + ROW_ANIM;
    const minTotal = currentRows.length ? (count <= 2 ? 6 : 7.5) : 4;
    return { start, stagger, total: Math.min(MAX_VIDEO, Math.max(minTotal, lastEnd + 1.4)) };
  };

  const slug = () => (currentDate || 'jadwal-sidang')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '');

  const saveBlob = (blob, filename) => {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 4000);
  };

  const newCanvas = () => {
    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;
    return canvas;
  };

  const drawPoster = async () => {
    if (document.fonts?.ready) {
      try { await document.fonts.ready; } catch (error) { /* pakai font cadangan */ }
    }
    await ensureLogos();
    const canvas = newCanvas();
    paintFrame(canvas.getContext('2d'), Number.POSITIVE_INFINITY);
    return canvas;
  };

  const downloadPng = async () => {
    const label = pngBtn.textContent;
    pngBtn.disabled = true;
    pngBtn.textContent = 'Menyiapkan…';
    try {
      const canvas = await drawPoster();
      const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
      if (blob) saveBlob(blob, `jadwal-sidang-pn-natuna-${slug()}.png`);
    } finally {
      pngBtn.disabled = false;
      pngBtn.textContent = label;
    }
  };

  const VIDEO_MIME = (() => {
    if (typeof MediaRecorder === 'undefined') return null;
    return [
      'video/mp4;codecs=avc1.42E01E',
      'video/mp4',
      'video/webm;codecs=vp9',
      'video/webm'
    ].find((type) => MediaRecorder.isTypeSupported(type)) || null;
  })();

  // MediaRecorder menulis durasi 0/potong pada moov, sehingga pemutar
  // menebak durasi terlalu pendek. Patch mvhd/tkhd/mdhd dengan durasi asli.
  const patchMp4Duration = (buffer, seconds) => {
    const bytes = new Uint8Array(buffer);
    const view = new DataView(buffer);
    const indexOf = (tag, from = 0) => {
      for (let i = from; i + 4 <= bytes.length; i += 1) {
        if (bytes[i] === tag.charCodeAt(0) && bytes[i + 1] === tag.charCodeAt(1)
          && bytes[i + 2] === tag.charCodeAt(2) && bytes[i + 3] === tag.charCodeAt(3)) return i;
      }
      return -1;
    };
    const u64 = (at) => view.getUint32(at) * 0x100000000 + view.getUint32(at + 4);
    const setU32 = (at, value) => view.setUint32(at, Math.min(0xffffffff, Math.max(0, Math.round(value))));

    const moov = indexOf('moov');
    if (moov === -1) return buffer;
    const mdat = indexOf('mdat', moov);
    const limit = mdat === -1 ? bytes.length : mdat;

    // mvhd/mdhd: timescale & duration; tkhd memakai timescale film.
    const patchTimed = (tag) => {
      const at = indexOf(tag, moov);
      if (at === -1 || at > limit) return;
      const wide = bytes[at + 4] === 1;
      const scaleAt = at + (wide ? 24 : 16);
      const durAt = at + (wide ? 28 : 20);
      const scale = view.getUint32(scaleAt) || 1000;
      const value = Math.round(seconds * scale);
      if (wide) {
        setU32(durAt, Math.floor(value / 0x100000000));
        setU32(durAt + 4, value % 0x100000000);
      } else {
        setU32(durAt, value);
      }
    };

    patchTimed('mvhd');
    patchTimed('mdhd');

    const tkhd = indexOf('tkhd', moov);
    if (tkhd !== -1 && tkhd < limit) {
      const wide = bytes[tkhd + 4] === 1;
      const mvhd = indexOf('mvhd', moov);
      let movieScale = 1000;
      if (mvhd !== -1) {
        const mvhdWide = bytes[mvhd + 4] === 1;
        movieScale = view.getUint32(mvhd + (mvhdWide ? 24 : 16)) || 1000;
      }
      const durAt = tkhd + (wide ? 32 : 24);
      const value = Math.round(seconds * movieScale);
      if (wide) {
        setU32(durAt, Math.floor(value / 0x100000000));
        setU32(durAt + 4, value % 0x100000000);
      } else {
        setU32(durAt, value);
      }
    }
    return buffer;
  };

  const downloadVideo = async () => {
    if (!VIDEO_MIME) return;
    const label = videoBtn.dataset.label || videoBtn.textContent;
    const canvas = await drawPoster();
    const ctx = canvas.getContext('2d');
    const { total } = timeline();
    // captureStream(30): compositor mengirim frame setiap kanvas berubah.
    const stream = canvas.captureStream(24);
    const recorder = new MediaRecorder(stream, { mimeType: VIDEO_MIME, videoBitsPerSecond: 12000000 });
    const chunks = [];
    recorder.ondataavailable = (event) => {
      if (event.data && event.data.size) chunks.push(event.data);
    };
    const finished = new Promise((resolve) => { recorder.onstop = resolve; });

    videoBtn.disabled = true;
    recorder.start(500);
    const startedAt = performance.now();
    // rAF untuk kelancaran; timer sebagai jaring pengaman karena rAF berhenti
    // total di tab background (rekaman akan menggantung di 0 detik).
    await new Promise((resolve) => {
      let done = false;
      function tick() {
        if (done) return;
        const elapsed = (performance.now() - startedAt) / 1000;
        const shownSeconds = Math.min(elapsed, total);
        paintFrame(ctx, shownSeconds);
        videoBtn.textContent = `Merekam ${shownSeconds.toFixed(1)}/${total.toFixed(1)} s`;
        if (elapsed >= total) {
          done = true;
          window.clearInterval(timer);
          resolve();
        }
      }
      const timer = window.setInterval(tick, 1000 / 24);
      const frame = () => {
        tick();
        if (!done) requestAnimationFrame(frame);
      };
      tick();
      requestAnimationFrame(frame);
    });
    // Beri waktu encoder mengirim frame terakhir sebelum menutup container.
    await new Promise((resolve) => { window.setTimeout(resolve, 260); });
    const recordedSeconds = (performance.now() - startedAt) / 1000;
    recorder.stop();
    await finished;

    const isMp4 = VIDEO_MIME.startsWith('video/mp4');
    if (chunks.length) {
      let blob = new Blob(chunks, { type: VIDEO_MIME });
      if (isMp4) {
        try {
          const patched = patchMp4Duration(await blob.arrayBuffer(), Math.min(recordedSeconds, total + 0.4));
          blob = new Blob([patched], { type: VIDEO_MIME });
        } catch (error) { /* biarkan metadata asli bila patch gagal */ }
      }
      saveBlob(blob, `jadwal-sidang-pn-natuna-${slug()}.${isMp4 ? 'mp4' : 'webm'}`);
    }
    videoBtn.disabled = false;
    videoBtn.textContent = label;
  };

  /* ---------- kontrol ---------- */

  triggers.forEach((trigger) => {
    trigger.hidden = false;
    trigger.addEventListener('click', () => open(trigger.dataset.sippExport, trigger));
  });

  closeBtn.addEventListener('click', close);
  stage.addEventListener('click', (event) => {
    if (event.target === stage) close();
  });

  const effectsToggle = stage.querySelector('[data-sipp-effects]');
  if (effectsToggle) {
    effectsToggle.addEventListener('change', () => {
      fullEffects = effectsToggle.checked;
    });
  }

  pngBtn.addEventListener('click', downloadPng);

  themeBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      poster.dataset.theme = btn.dataset.sippTheme;
      themeBtns.forEach((item) => item.setAttribute('aria-pressed', item === btn ? 'true' : 'false'));
    });
  });

  if (VIDEO_MIME) {
    videoBtn.textContent = `Unduh video ${VIDEO_MIME.startsWith('video/mp4') ? 'MP4' : 'WebM'}`;
    videoBtn.addEventListener('click', downloadVideo);
  } else {
    videoBtn.textContent = 'Video tidak didukung browser';
    videoBtn.disabled = true;
  }
  videoBtn.dataset.label = videoBtn.textContent;

  fullBtn.addEventListener('click', () => {
    if (document.fullscreenElement) {
      document.exitFullscreen?.().catch(() => {});
      return;
    }
    stage.requestFullscreen?.().catch(() => {});
  });

  document.addEventListener('fullscreenchange', () => {
    fullBtn.textContent = document.fullscreenElement ? 'Keluar layar penuh' : 'Layar penuh';
    window.setTimeout(fitPoster, 120);
  });

  printBtn.addEventListener('click', () => {
    document.body.classList.add('sipp-stage-print');
    const cleanup = () => document.body.classList.remove('sipp-stage-print');
    window.addEventListener('afterprint', cleanup, { once: true });
    window.print();
    window.setTimeout(cleanup, 2000);
  });

  window.addEventListener('resize', () => {
    if (!stage.hidden) fitPoster();
  });

  stage.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
      return;
    }
    if (event.key !== 'Tab') return;
    const focusable = Array.from(
      stage.querySelectorAll('button, [href], [tabindex]:not([tabindex="-1"])')
    ).filter((element) => !element.disabled && element.offsetParent !== null);
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });
})();
