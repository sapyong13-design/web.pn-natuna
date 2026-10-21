(() => {
  'use strict';

  const stage = document.getElementById('sipp-stage');
  const poster = document.getElementById('sipp-poster');
  if (!stage || !poster) return;

  const triggers = Array.from(document.querySelectorAll('[data-sipp-export]'));
  if (!triggers.length) return;

  const listEl = document.getElementById('sipp-poster-list');
  const dateEl = document.getElementById('sipp-poster-date');
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
    const tab = document.getElementById(`sipp-tab-${key}`);
    currentDate = fullDateLabel((tab?.querySelector('span')?.textContent || '').trim());

    const rooms = new Set(currentRows.map((row) => row.room).filter(Boolean));
    dateEl.textContent = currentDate;
    metaEl.textContent = currentRows.length
      ? `${currentRows.length} perkara${rooms.size > 1 ? ` · ${rooms.size} ruang sidang` : ''}`
      : 'Tidak ada perkara terjadwal';

    const updated = readUpdated(panel);
    updatedEl.textContent = updated ? `Data per ${expandMonthInText(updated)}` : '';

    // Kepadatan ditentukan lebih dulu karena memengaruhi jumlah baris per kartu.
    const total = currentRows.length;
    const density = total > 8 ? 'dense' : total > 6 ? 'compact' : total <= 4 ? 'airy' : 'normal';
    poster.dataset.density = density;

    // Hari dengan sedikit perkara menyisakan ruang; isi dengan catatan resmi
    // supaya poster tidak terlihat kosong.
    const showNote = currentRows.length > 0 && currentRows.length <= 6;
    noteEl.hidden = !showNote;
    if (showNote) {
      noteEl.textContent = 'NB: Jadwal dapat berubah sewaktu-waktu.';
    }

    listEl.textContent = '';
    currentRows.forEach((row, index) => listEl.appendChild(buildRow(row, index, density === 'dense')));

    if (!currentRows.length) {
      const empty = document.createElement('li');
      empty.className = 'sipp-poster__empty';
      empty.textContent = 'Belum ada jadwal sidang pada hari ini.';
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
  const ensureLogos = async () => {
    if (!PN_LOGO || !ASN_LOGO) {
      const [pn, asn] = await Promise.all([
        loadImage('/images/brand/logo-pn-natuna-mark.webp'),
        loadImage('/images/brand/logo-asn-berakhlak.webp')
      ]);
      PN_LOGO = pn;
      ASN_LOGO = asn;
    }
  };

  /* ---------- Lukis poster: PNG & video 1080 x 1350 ---------- */

  const W = 1080;
  const H = 1920;
  const PAD = 72;
  const ROW_ANIM = 0.45;
  const HEAD_ANIM = 0.55;
  const MAX_VIDEO = 10;

  const THEMES = {
    maroon: {
      bg: ['#2f140e', '#5c1508', '#7d2a14'],
      barTop: 'rgba(234, 216, 167, 0.10)',
      court: '#ead395', sub: 'rgba(244, 227, 189, 0.72)',
      title: '#ffffff', date: '#ead395', meta: 'rgba(244, 227, 189, 0.82)',
      divider: 'rgba(234, 216, 167, 0.35)',
      cardBg: 'rgba(255, 255, 255, 0.06)', cardLine: 'rgba(234, 216, 167, 0.18)',
      caseText: '#ffffff', lineText: 'rgba(244, 227, 189, 0.86)', agendaText: 'rgba(244, 227, 189, 0.95)',
      foot: 'rgba(244, 227, 189, 0.66)'
    },
    navy: {
      bg: ['#07131d', '#0f2735', '#17415a'],
      barTop: 'rgba(240, 201, 135, 0.12)',
      court: '#f0c987', sub: 'rgba(226, 236, 244, 0.72)',
      title: '#ffffff', date: '#f0c987', meta: 'rgba(226, 236, 244, 0.82)',
      divider: 'rgba(240, 201, 135, 0.32)',
      cardBg: 'rgba(255, 255, 255, 0.07)', cardLine: 'rgba(240, 201, 135, 0.18)',
      caseText: '#ffffff', lineText: 'rgba(226, 236, 244, 0.86)', agendaText: 'rgba(226, 236, 244, 0.95)',
      foot: 'rgba(226, 236, 244, 0.66)'
    },
    cream: {
      bg: ['#fbf6ec', '#f3ead8', '#e9dcc3'],
      barTop: 'rgba(47, 20, 14, 0.08)',
      court: '#7a3d10', sub: 'rgba(47, 20, 14, 0.62)',
      title: '#2f140e', date: '#8a5a12', meta: 'rgba(47, 20, 14, 0.72)',
      divider: 'rgba(47, 20, 14, 0.2)',
      cardBg: 'rgba(255, 255, 255, 0.78)', cardLine: 'rgba(47, 20, 14, 0.12)',
      caseText: '#2f140e', lineText: 'rgba(47, 20, 14, 0.78)', agendaText: 'rgba(47, 20, 14, 0.92)',
      foot: 'rgba(47, 20, 14, 0.55)'
    }
  };

  const easeOut = (p) => 1 - Math.pow(1 - p, 3);
  const clamp01 = (value) => Math.min(1, Math.max(0, value));
  const palette = () => THEMES[poster.dataset.theme] || THEMES.maroon;

  // Kepala poster selalu berakhir di titik yang sama, jadi area kartu tetap.
  const AREA_TOP = PAD - 4 + 88 + 28 + 212 + 34;
  const AREA_BOTTOM = H - PAD - 70;

  const layoutFor = (count) => {
    const dense = count > 8;
    const compact = count > 6 && !dense;
    const airy = count <= 4;

    let caseSize = airy ? 50 : compact ? 34 : 42;
    let lineSize = airy ? 30 : compact ? 22 : 27;
    let agendaSize = airy ? 31 : compact ? 23 : 28;
    let padY = airy ? 26 : compact ? 14 : 20;
    const innerGap = 7;

    const measure = () => Math.round(
      caseSize * 1.18 + (innerGap + lineSize * 1.18) + (innerGap + agendaSize * 1.18) + padY * 2
    );
    let cardH = measure();

    // Kalau blok kartu melebihi area, kecilkan tipografi sampai muat.
    const gap = 16;
    const available = AREA_BOTTOM - AREA_TOP;
    let guard = 0;
    while (count * cardH + (count - 1) * gap > available && guard < 12) {
      caseSize *= 0.94;
      lineSize *= 0.94;
      agendaSize *= 0.94;
      padY *= 0.94;
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
  const drawCard = (ctx, row, layout, y, pal, dense) => {
    const x = PAD;
    const w = W - PAD * 2;
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
    ctx.shadowBlur = 30;
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
        ctx.globalAlpha = 0.85;
        ctx.fillStyle = tone.neon;
        ctx.fillText(label, textX, textY);
        ctx.filter = 'blur(7px)';
        ctx.globalAlpha = 1;
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

  const paintFrame = (ctx, time) => {
    const pal = palette();
    const t = Number.isFinite(time) ? time : Number.POSITIVE_INFINITY;
    const dense = poster.dataset.density === 'dense';

    const bg = ctx.createLinearGradient(0, 0, W, H);
    bg.addColorStop(0, pal.bg[0]);
    bg.addColorStop(0.55, pal.bg[1]);
    bg.addColorStop(1, pal.bg[2]);
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, W, H);

    ctx.fillStyle = pal.barTop;
    ctx.fillRect(0, 0, W, 8);
    ctx.fillStyle = '#b98f24';
    ctx.fillRect(0, 0, W * 0.34, 8);

    ctx.textBaseline = 'alphabetic';
    ctx.textAlign = 'left';

    const headAlpha = t === Infinity ? 1 : easeOut(clamp01(t / HEAD_ANIM));
    ctx.save();
    ctx.globalAlpha = headAlpha;

    const topY = PAD - 4;
    const markH = 88;
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
    if (ASN_LOGO && ASN_LOGO.naturalWidth) {
      const asnH = markH;
      const asnW = asnH * (ASN_LOGO.naturalWidth / ASN_LOGO.naturalHeight);
      const badgeW = asnW + 32;
      const badgeH = asnH + 22;
      const badgeX = W - PAD - badgeW;
      const badgeY = topY - 11;
      ctx.fillStyle = 'rgba(255, 255, 255, 0.94)';
      roundRect(ctx, badgeX, badgeY, badgeW, badgeH, 14);
      ctx.fill();
      ctx.drawImage(ASN_LOGO, badgeX + 16, badgeY + 11, asnW, asnH);
      brandMax = badgeX - brandX - 20;
    }

    ctx.fillStyle = pal.court;
    ctx.font = `700 20px ${SANS}`;
    if ('letterSpacing' in ctx) ctx.letterSpacing = '2.6px';
    ctx.fillText(fitText(ctx, 'PENGADILAN NEGERI NATUNA', brandMax), brandX, topY + 30);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

    ctx.fillStyle = pal.sub;
    ctx.font = `400 17px ${SANS}`;
    ctx.fillText(fitText(ctx, 'Kelas II · Kabupaten Natuna', brandMax), brandX, topY + 62);

    const headTop = topY + markH + 28;
    ctx.fillStyle = pal.title;
    ctx.font = `700 86px ${SERIF}`;
    ctx.fillText(fitText(ctx, 'Jadwal Sidang', W - PAD * 2), PAD, headTop + 82);

    ctx.fillStyle = pal.date;
    ctx.font = `600 50px ${SERIF}`;
    ctx.fillText(fitText(ctx, currentDate, W - PAD * 2), PAD, headTop + 142);

    ctx.fillStyle = pal.meta;
    ctx.font = `500 26px ${SANS}`;
    ctx.fillText(fitText(ctx, metaEl.textContent, W - PAD * 2), PAD, headTop + 180);
    ctx.restore();

    const dividerY = headTop + 212;
    ctx.save();
    ctx.globalAlpha = headAlpha;
    ctx.strokeStyle = pal.divider;
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(PAD, dividerY);
    ctx.lineTo(W - PAD, dividerY);
    ctx.stroke();
    ctx.restore();

    const { start, stagger } = timeline();
    const layout = layoutFor(currentRows.length);
    const blockH = currentRows.length * layout.cardH + Math.max(0, currentRows.length - 1) * layout.gap;
    const noteOn = currentRows.length > 0 && currentRows.length <= 6;
    const noteH = 66;
    const usableBottom = noteOn ? AREA_BOTTOM - noteH - 18 : AREA_BOTTOM;
    const blockTop = AREA_TOP + Math.max(0, (usableBottom - AREA_TOP - blockH) / 2);
    currentRows.forEach((row, index) => {
      const p = t === Infinity ? 1 : easeOut(clamp01((t - (start + index * stagger)) / ROW_ANIM));
      if (p <= 0) return;
      ctx.save();
      ctx.globalAlpha = p;
      ctx.translate(0, (1 - p) * 26);
      drawCard(ctx, row, layout, blockTop + index * (layout.cardH + layout.gap), pal, dense);
      ctx.restore();
    });

    if (noteOn) {
      const noteP = t === Infinity ? 1 : easeOut(clamp01((t - start - 0.4) / 0.6));
      const boxW = W - PAD * 2;
      const boxY = AREA_BOTTOM - noteH;
      ctx.save();
      ctx.globalAlpha = noteP;
      ctx.fillStyle = 'rgba(255, 255, 255, 0.05)';
      roundRect(ctx, PAD, boxY, boxW, noteH, 14);
      ctx.fill();
      ctx.strokeStyle = pal.cardLine;
      ctx.lineWidth = 1.5;
      ctx.stroke();

      ctx.textAlign = 'center';
      ctx.fillStyle = pal.date;
      ctx.font = `700 25px ${SANS}`;
      ctx.fillText(fitText(ctx, 'NB: Jadwal dapat berubah sewaktu-waktu.', boxW - 60), W / 2, boxY + 42);
      ctx.textAlign = 'left';
      ctx.restore();
    }

    const footP = t === Infinity ? 1 : easeOut(clamp01((t - start) / 0.6));
    ctx.save();
    ctx.globalAlpha = footP;
    ctx.fillStyle = pal.foot;
    ctx.font = `400 22px ${SANS}`;
    const footerText = updatedEl.textContent
      ? `Sumber: SIPP PN Natuna · ${updatedEl.textContent}`
      : 'Sumber: SIPP PN Natuna';
    ctx.fillText(fitText(ctx, footerText, W - PAD * 2 - 170), PAD, H - PAD - 8);

    ctx.textAlign = 'right';
    ctx.fillStyle = pal.court;
    ctx.font = `600 22px ${SANS}`;
    ctx.fillText('pn-natuna.go.id', W - PAD, H - PAD - 8);
    ctx.textAlign = 'left';
    ctx.restore();
  };

  // Satu kartu muncul tiap `stagger` detik; total selalu di bawah 10 detik.
  const timeline = () => {
    const count = Math.max(currentRows.length, 1);
    const start = 0.9;
    const target = 7;
    const allIn = 6; // seluruh kartu selesai muncul
    const stagger = count > 1
      ? Math.min(0.9, Math.max(0.35, (allIn - start - ROW_ANIM) / (count - 1)))
      : 0.5;
    const lastEnd = start + (count - 1) * stagger + ROW_ANIM;
    return { start, stagger, total: Math.min(MAX_VIDEO, Math.max(target, lastEnd + 0.8)) };
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
    const stream = canvas.captureStream(30);
    const recorder = new MediaRecorder(stream, { mimeType: VIDEO_MIME, videoBitsPerSecond: 8000000 });
    const chunks = [];
    recorder.ondataavailable = (event) => {
      if (event.data && event.data.size) chunks.push(event.data);
    };
    const finished = new Promise((resolve) => { recorder.onstop = resolve; });

    videoBtn.disabled = true;
    recorder.start();
    const startedAt = performance.now();
    await new Promise((resolve) => {
      const frame = () => {
        const elapsed = (performance.now() - startedAt) / 1000;
        paintFrame(ctx, Math.min(elapsed, total));
        videoBtn.textContent = `Merekam ${Math.min(elapsed, total).toFixed(1)}/${total.toFixed(1)} s`;
        if (elapsed >= total) {
          resolve();
          return;
        }
        requestAnimationFrame(frame);
      };
      frame();
    });
    recorder.stop();
    await finished;

    const isMp4 = VIDEO_MIME.startsWith('video/mp4');
    if (chunks.length) {
      let blob = new Blob(chunks, { type: VIDEO_MIME });
      if (isMp4) {
        try {
          const patched = patchMp4Duration(await blob.arrayBuffer(), total);
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
