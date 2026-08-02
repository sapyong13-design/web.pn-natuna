<?php
/**
 * Kontrak data sistem resmi daring.
 *
 * Pencarian situs hanya mengindeks artikel. Tujuh sistem resmi di luar situs - SIPP,
 * e-Court, e-Berpadu, SIWAS, Direktori Putusan, Eksekusi Badilum - tidak akan pernah
 * muncul di hasil, padahal ke sanalah warga perlu diantar untuk jadwal sidang,
 * penelusuran perkara, pengaduan, dan salinan putusan. Daftar kuratornya ada di
 * `templates/pn_natuna_2026/data/sistem-daring.json`.
 *
 * Dua bahaya yang dijaga kontrak ini. Pertama, daftar itu mengantar warga keluar dari
 * situs pengadilan, jadi tujuannya wajib domain resmi - satu URL salah ketik bisa
 * mengirim orang ke tempat yang tidak semestinya. Kedua, kata kunci pemicunya tidak
 * boleh serakah: sebelum blok ini ada, pencarian "biaya perkara", "ptsp", "mediasi",
 * "prodeo", "kontak", "zona integritas", dan "posbakum" sudah mengembalikan halaman yang
 * benar di urutan teratas. Kata kunci yang menabrak salah satunya akan menutupi hasil
 * yang sudah tepat dengan tautan keluar - itu kemunduran, bukan perbaikan.
 */
$root = dirname(__DIR__);
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$path = $root . '/templates/pn_natuna_2026/data/sistem-daring.json';
$expect(is_file($path), 'The curated list of official online systems is missing.');

if (!is_file($path)) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

$raw = (string) file_get_contents($path);
$data = json_decode($raw, true);
$expect(json_last_error() === JSON_ERROR_NONE, 'sistem-daring.json is not valid JSON: ' . json_last_error_msg());
$sistem = \is_array($data['sistem'] ?? null) ? $data['sistem'] : [];
$expect($sistem !== [], 'sistem-daring.json carries no entries.');

// Kueri yang sudah mengembalikan halaman yang benar tanpa bantuan; blok tujuan tidak
// boleh membajaknya.
$sudahBenar = ['biaya perkara', 'ptsp', 'mediasi', 'prodeo', 'kontak', 'zona integritas', 'posbakum'];
$idTerlihat = [];
$kataTerlihat = [];

foreach ($sistem as $i => $entry) {
    $label = $entry['id'] ?? "#{$i}";
    foreach (['id', 'nama', 'url', 'keterangan', 'kataKunci'] as $key) {
        $expect(isset($entry[$key]) && $entry[$key] !== '' && $entry[$key] !== [], "Entry {$label} is missing `{$key}`.");
    }
    $id = (string) ($entry['id'] ?? '');
    $expect(!isset($idTerlihat[$id]), "Entry id {$id} appears twice.");
    $idTerlihat[$id] = true;

    // Tujuan wajib domain resmi peradilan.
    $host = parse_url((string) ($entry['url'] ?? ''), PHP_URL_HOST) ?: '';
    $expect(
        (bool) preg_match('/(^|\.)(mahkamahagung\.go\.id|pn-natuna\.go\.id)$/', $host),
        "Entry {$label} points at {$host}, which is not an official court domain."
    );
    $expect(str_starts_with((string) ($entry['url'] ?? ''), 'https://'), "Entry {$label} must use https.");

    foreach ((array) ($entry['kataKunci'] ?? []) as $kata) {
        $kata = (string) $kata;
        $expect($kata === mb_strtolower($kata), "Keyword \"{$kata}\" in {$label} must be lowercase; matching is done on a lowercased query.");
        $expect(mb_strlen($kata) >= 4, "Keyword \"{$kata}\" in {$label} is too short to be a deliberate trigger.");
        $expect(!isset($kataTerlihat[$kata]), "Keyword \"{$kata}\" is claimed by two systems; the first would always win.");
        $kataTerlihat[$kata] = $label;

        foreach ($sudahBenar as $aman) {
            $expect(
                $kata !== $aman,
                "Keyword \"{$kata}\" in {$label} hijacks \"{$aman}\", a query whose top result is already the correct page."
            );
        }
    }
}

// Sanity: entri yang menjawab kegagalan nyata harus ada.
foreach (['sipp-jadwal' => 'jadwal sidang has no page on this site at all', 'direktori-putusan' => '"salinan putusan" used to surface Prosedur Eksekusi', 'siwas' => '"pengaduan" used to surface a regulation article'] as $id => $why) {
    $expect(isset($idTerlihat[$id]), "Entry {$id} is missing; it exists because {$why}.");
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo 'online systems contract: ok (' . \count($sistem) . " systems, " . \count($kataTerlihat) . " keywords)\n";
