START TRANSACTION;
UPDATE pnn_content SET introtext='<div class="struktur-page">
  <section class="struktur-hero-card">
    <div>
      <p class="struktur-kicker">Bagan kelembagaan</p>
      <h2>Struktur Organisasi Pengadilan Negeri Natuna Kelas II</h2>
      <p>Struktur organisasi Pengadilan Negeri Natuna Kelas II disusun untuk mendukung pelaksanaan tugas teknis peradilan, administrasi perkara, administrasi umum, serta pelayanan kepada masyarakat.</p>
    </div>
    <div class="struktur-focus-list" aria-label="Fokus dukungan struktur organisasi">
      <span>Teknis peradilan</span>
      <span>Administrasi perkara</span>
      <span>Administrasi umum</span>
      <span>Pelayanan masyarakat</span>
    </div>
  </section>

  <section class="struktur-chart-card" aria-label="Bagan struktur organisasi">
    <div class="struktur-chart-heading">
      <div>
        <p>Bagan organisasi</p>
        <h3>Pengadilan Negeri Natuna Kelas II</h3>
      </div>
      <a href="/images/profil/struktur-organisasi.png" target="_blank" rel="noopener">Buka gambar penuh</a>
    </div>
    <a class="struktur-chart-link" href="/images/profil/struktur-organisasi.png" target="_blank" rel="noopener" aria-label="Buka bagan struktur organisasi dalam ukuran penuh">
      <img src="/images/profil/struktur-organisasi.png" alt="Struktur organisasi Pengadilan Negeri Natuna" width="1500" height="1061" loading="eager" decoding="async">
    </a>
  </section>
</div>' WHERE id=56;
COMMIT;
