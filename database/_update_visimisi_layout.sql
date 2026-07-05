START TRANSACTION;
UPDATE pnn_content SET introtext='<div class="visimisi-page">
  <section class="visimisi-hero-card">
    <div class="visimisi-hero-copy">
      <p class="visimisi-kicker">Arah layanan peradilan</p>
      <h2>Visi dan Misi Pengadilan Negeri Natuna Kelas II</h2>
      <p class="visimisi-lead">Visi dan misi menjadi arah kerja Pengadilan Negeri Natuna Kelas II dalam menjaga kemandirian peradilan, meningkatkan kualitas pelayanan hukum, serta membangun kepercayaan masyarakat melalui pelayanan yang transparan dan berkeadilan.</p>
    </div>
    <figure class="visimisi-illustration">
      <img src="/images/ilustrasi/visi-misi-pn-natuna.svg" alt="Ilustrasi visi misi Pengadilan Negeri Natuna" width="960" height="720" loading="eager" decoding="async">
      <figcaption>Ilustrasi arah pelayanan: independensi, keadilan, transparansi, dan pelayanan publik.</figcaption>
    </figure>
  </section>

  <section class="visimisi-vision-card" aria-label="Visi Pengadilan Negeri Natuna">
    <span>Visi</span>
    <blockquote>Terwujudnya Pengadilan Negeri Natuna Kelas II yang Agung.</blockquote>
    <p>Visi ini menegaskan komitmen pengadilan untuk menghadirkan lembaga peradilan yang mandiri, berintegritas, profesional, dan dipercaya masyarakat.</p>
  </section>

  <section class="visimisi-mission-section" aria-label="Misi Pengadilan Negeri Natuna">
    <div class="visimisi-section-heading">
      <p>Misi</p>
      <h3>Empat arah kerja utama</h3>
    </div>
    <div class="visimisi-mission-grid">
      <article>
        <span>01</span>
        <h4>Menjaga kemandirian pengadilan</h4>
        <p>Menegakkan prinsip independensi peradilan agar setiap perkara diperiksa dan diputus secara objektif, imparsial, dan berdasarkan hukum.</p>
      </article>
      <article>
        <span>02</span>
        <h4>Memberikan pelayanan hukum berkeadilan</h4>
        <p>Menyediakan pelayanan bagi pencari keadilan secara sederhana, cepat, transparan, akuntabel, dan mudah diakses oleh seluruh masyarakat Natuna.</p>
      </article>
      <article>
        <span>03</span>
        <h4>Meningkatkan kualitas kepemimpinan</h4>
        <p>Mendorong tata kelola kelembagaan yang efektif melalui kepemimpinan yang responsif, pembinaan aparatur, dan pengawasan berkelanjutan.</p>
      </article>
      <article>
        <span>04</span>
        <h4>Meningkatkan kredibilitas dan transparansi</h4>
        <p>Menguatkan kepercayaan publik melalui keterbukaan informasi, integritas aparatur, layanan digital, dan pelaksanaan tugas yang dapat dipertanggungjawabkan.</p>
      </article>
    </div>
  </section>

  <section class="visimisi-values-card">
    <strong>Nilai pelayanan</strong>
    <div>
      <span>Mandiri</span>
      <span>Berintegritas</span>
      <span>Profesional</span>
      <span>Transparan</span>
      <span>Akuntabel</span>
    </div>
  </section>
</div>', `fulltext`='' WHERE id=10;
COMMIT;
