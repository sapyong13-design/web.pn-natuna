<?php
/** Blok penunjuk ke sistem resmi; tidak memeriksa atau memuat data sistem tersebut. */
defined('_JEXEC') or die;
?>
<section class="site-search__case-route" aria-labelledby="case-route-title">
    <p class="site-search__case-route-kicker">Rute layanan resmi</p>
    <h2 id="case-route-title">Sistem resmi Mahkamah Agung</h2>
    <p>Pencarian situs tidak memeriksa data pada sistem daring berikut. Gunakan layanan resmi sesuai keperluan Anda.</p>
    <ul class="site-search__case-route-links">
        <?php foreach ($caseSystems as $sistem) : ?>
            <li>
                <a href="<?php echo htmlspecialchars((string) $sistem['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                    <span><?php echo htmlspecialchars((string) $sistem['nama'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span aria-hidden="true">(situs luar)</span>
                    <span class="visually-hidden">, membuka situs luar pada tab baru</span>
                </a>
                <p><?php echo htmlspecialchars((string) $sistem['keterangan'], ENT_QUOTES, 'UTF-8'); ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="site-search__case-route-secondary"><a href="/#sipp-schedule-title">Lihat ringkasan jadwal sidang di Beranda</a></p>
</section>
