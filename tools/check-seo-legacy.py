"""Run: python tools/check-seo-legacy.py -- verifies production SEO mappings."""
import urllib.error
import urllib.request


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None


def get(url):
    try:
        return opener.open(url, timeout=30)
    except urllib.error.HTTPError as error:
        return error


opener = urllib.request.build_opener(NoRedirect)
base = 'https://pn-natuna.go.id'
mappings = {
    'about/profil-pengadilan/sejarah-pengadilan': '/profil-pengadilan/sejarah-pengadilan',
    'artikel-pn-natuna/uncategorised/mediasi': '/layanan-hukum/mediasi',
    'about/kepaniteraan': '/profil-pengadilan/profil-kepaniteraan',
    "about/profil-pengadilan": "/profil-pengadilan",
    "about/profil-pengadilan/wilayah-yuridiksi": "/profil-pengadilan/wilayah-yurisdiksi",
    "about/kepaniteraan/kepaniteraan-hukum": "/profil-pengadilan/profil-kepaniteraan/kepaniteraan-hukum",
    "about/profil-hakim-dan-pegawai/profil-kepaniteraan": "/profil-pengadilan/profil-kepaniteraan",
    "about/profil-hakim-dan-pegawai/profil-kesekretariatan": "/profil-pengadilan/profil-kesekretariatan",
    "artikel-pn-natuna/uncategorised/visi-dan-misi-pengadilan-negeri-2": "/profil-pengadilan/visi-misi",
    "layanan-publik/informasi-perkara": "/informasi-perkara",
    "layanan-publik/informasi-perkara/delegasi": "/layanan-hukum/delegasi",
    "layanan-hukum/prosedur-peminjaman-berkas": "/layanan-hukum/peminjaman-berkas",
    "layanan-publik/ptsp/jenis-layanan": "/layanan-publik/jenis-layanan-ptsp",
    "layanan-publik/ptsp/standar-pelayanan": "/layanan-publik/standar-pelayanan",
    "layanan-publik/laporan/laporan-keuangan-pn": "/transparansi/laporan-keuangan",
    "layanan-publik/laporan/laporan-skm": "/transparansi/laporan-skm",
    "layanan-publik/laporan/laporan-spak": "/transparansi/laporan-spak",
    "layanan-hukum/layanan-hukum-bagi-masyarakat-yang-kurang-mampu/posbakum": "/layanan-hukum/posbakum",
    "layanan-hukum/layanan-hukum-bagi-masyarakat-yang-kurang-mampu/zitting-plaats": "/layanan-hukum/zitting-plaats",
    "layanan-hukum/prosedur-pengajuan-perkara-dan-biaya-perkara/biaya-perkara": "/informasi-perkara/biaya-perkara",
    "layanan-hukum/prosedur-pengajuan-perkara-dan-biaya-perkara/prosedur-pengajuan-perkara": "/informasi-perkara/prosedur-pengajuan-perkara",
    "reformasi-birokrasi/zona-integritas": "/zona-integritas",
    "reformasi-birokrasi/zona-integritas/area-i": "/zona-integritas/area-i",
    "reformasi-birokrasi/zona-integritas/area-ii": "/zona-integritas/area-ii",
    "reformasi-birokrasi/zona-integritas/area-iv": "/zona-integritas/area-iv",
    "berita/berita-terkini/pengambilan-sumpah-janji-jabatan-dan-pelantikan-panitera-penganti-pada-pengadilan-negeri-natuna": "/berita/pengambilan-sumpah-janji-jabatan-dan-pelantikan-panitera-penganti-pada-pengadilan-negeri-natuna",
    "berita/berita-terkini/pelaksanaan-upacara-hari-kebangkitan-nasional-ke-118-tahun-2026": "/berita/pelaksanaan-upacara-hari-kebangkitan-nasional-ke-118-tahun-2026",
    "berita/berita-terkini/kegiatan-rakornas-kpai-tahun-2023": "/berita/kegiatan-rakornas-kpai-tahun-2023",
    "berita/berita-terkini/penandatanganan-mou-posbakum": "/berita/penandatanganan-mou-posbakum",
    "berita/berita-terkini/rapat-bulanan-periode-februari-2024": "/berita/rapat-bulanan-periode-februari-2024",
    "berita/berita-terkini/pembinaan-bidang-teknis-dan-administrasi-yudisial-09-oktober-2023": "/berita/pembinaan-bidang-teknis-dan-administrasi-yudisial-09-oktober-2023",
    "berita/berita-terkini/risalah-panggilan-pemberitahuan-umum-kepada-tergugat-nomor-5-pdt-g-2024-pn-ntn-panggilan-ke-2": "/berita/risalah-panggilan-pemberitahuan-umum-kepada-tergugat-nomor-5-pdt-g-2024-pn-ntn-panggilan-ke-2",
    "berita/berita-terkini/seleksi-pengadaan-hakim-dari-analis-perkara-peradilan-formasi-tahun-2021-tahapan-substansi-hukum": "/berita/seleksi-pengadaan-hakim-dari-analis-perkara-peradilan-formasi-tahun-2021-tahapan-substansi-hukum",
    "berita/berita-terkini/pengambilan-sumpah-janji-jabatan-dan-pelantikan-hakim-pratama-pada-pengadilan-negeri-natuna": "/berita/pengambilan-sumpah-janji-jabatan-dan-pelantikan-hakim-pratama-pada-pengadilan-negeri-natuna",
    "berita/berita-terkini/mou-antara-pengadilan-negeri-natuna-dengan-pt-pos-indonesia-kc-tanjungpinang": "/berita/mou-antara-pengadilan-negeri-natuna-dengan-pt-pos-indonesia-kc-tanjungpinang",
    "berita/berita-terkini/pelaksanaan-upcara-bendera-hut-ri-ke-80": "/berita/pelaksanaan-upcara-bendera-hut-ri-ke-80",
    "berita/berita-terkini/kegiatan-senam-bersama": "/berita/kegiatan-senam-bersama",
    "berita/berita-terkini/rapat-bulanan-periode-april-2024": "/berita/rapat-bulanan-periode-april-2024",
    "berita/berita-terkini/upacara-peringatan-hari-kesaktian-pancasila-1-oktober-2023": "/berita/upacara-peringatan-hari-kesaktian-pancasila-1-oktober-2023",
    "berita/berita-terkini/upacara-peringatan-hari-sumpah-pemuda-28-oktober-2023": "/berita/upacara-peringatan-hari-sumpah-pemuda-28-oktober-2023",
    "berita/berita-terkini/pengambilan-sumpah-dan-pelantikan-cpns-menjadi-pns": "/berita/pengambilan-sumpah-dan-pelantikan-cpns-menjadi-pns",
    "berita/berita-terkini/pelaksanaan-upcara-hut-mahkamah-agung-ke-80": "/berita/pelaksanaan-upcara-hut-mahkamah-agung-ke-80",
    "berita/berita-terkini/pelaksanaan-upacara-hari-lahir-pancasila": "/berita/pelaksanaan-upacara-hari-lahir-pancasila",
    "berita/berita-terkini/briefing-pagi-petugas-ptsp-pengadilan-negeri-natuna": "/berita/briefing-pagi-petugas-ptsp-pengadilan-negeri-natuna",
    "berita/berita-terkini/laporan-tahunan-mahkamah-agung-tahun-2023": "/berita/laporan-tahunan-mahkamah-agung-tahun-2023",
    "berita/berita-terkini/kunjungan-silaturahmi-kajari-natuna-dan-kacabjari-tarempa-ke-pengadilan-negeri-natuna": "/berita/kunjungan-silaturahmi-kajari-natuna-dan-kacabjari-tarempa-ke-pengadilan-negeri-natuna",
    "berita/berita-terkini/penandatanganan-mou-posbakum-tahun-2025": "/berita/penandatanganan-mou-posbakum-tahun-2025",
    "berita/berita-terkini/pengambilan-sumpahjabatan-dan-pelantikan-panitera-penganti-pada-pengadilan-negeri-natuna-kelas-ii": "/berita/pengambilan-sumpahjabatan-dan-pelantikan-panitera-penganti-pada-pengadilan-negeri-natuna-kelas-ii",
    "berita/berita-terkini/pelantikan-dan-pengambilan-sumpah-jabatan-sekretaris-pengadilan-negeri-natuna": "/berita/pelantikan-dan-pengambilan-sumpah-jabatan-sekretaris-pengadilan-negeri-natuna",
    "berita/berita-terkini/kegiatan-penandatanganan-pakta-integritas-kpn-sewilayah-hukum-pt-kepulauan-riau": "/berita/kegiatan-penandatanganan-pakta-integritas-kpn-sewilayah-hukum-pt-kepulauan-riau",
    "berita/berita-terkini/rapat-forum-koordinasi-pimpinan-daerah-kabupaten-kepulauan-anambas-tahun-2024": "/berita/rapat-forum-koordinasi-pimpinan-daerah-kabupaten-kepulauan-anambas-tahun-2024",
}
for old, target in mappings.items():
    for prefix in ('/index.php/en/', '/index.php/', '/'):
        with get(base + prefix + old) as response:
            assert response.code == 301, (old, response.code)
            assert response.headers['Location'] == base + target, (old, response.headers['Location'])
    with get(base + target) as response:
        assert response.code == 200, (target, response.code)
    print('PASS', old, target)
with get(base + '/seo-nonexistent-probe') as response:
    assert response.code == 404, response.code
print('PASS unknown URL remains 404')
for path in ('/', '/profil-pengadilan', '/profil-pengadilan?utm_source=seo-check'):
    with get('https://www.pn-natuna.go.id' + path) as response:
        assert response.code == 301, (path, response.code)
        assert response.headers['Location'] == base + path, response.headers['Location']
with get(base + '/beranda') as response:
    assert response.code == 301 and response.headers['Location'] == base + '/'
with get(base + '/') as response:
    assert response.code == 200, response.code
    assert '<link href="https://pn-natuna.go.id/" rel="canonical">' in response.read().decode()
print('PASS canonical host, query preservation, and homepage')
