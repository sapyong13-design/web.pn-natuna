<?php
defined('_JEXEC') or die;

/**
 * Mengangkat dateline pembuka berita menjadi satu elemen kedinasan.
 *
 * Lima dari enam berita membuka dengan tempat dan tanggal peristiwa dalam empat
 * bentuk berbeda: "Natuna - ", "Natuna, 10 Juli 2026.", "Natuna, 29 Juni 2026,",
 * dan "Natuna. Rabu, 03/06/2026,". Setelah diangkat, tanggal peristiwa berhenti
 * bertabrakan dengan "Terbit" di baris meta - artikel CPNS berperistiwa 3 Juni
 * tetapi terbit 4 Juni.
 *
 * Kata di luar dateline tidak pernah dibuang; hanya huruf pertama sisa kalimat
 * yang dinaikkan bila tadinya huruf kecil. Badan dikembalikan apa adanya bila
 * paragraf pembukanya tidak berdateline.
 */
function pn_natuna_article_dateline(string $body): string
{
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $dateline = '/^\s*(?P<place>Natuna|Ranai|Tarempa|Anambas|Midai|Serasan|Bunguran|Batam|Tanjungpinang|Jakarta)\s*(?P<lead>[\x{2013}\x{2014}-]|[,.])\s*'
        . '(?:(?:Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu),?\s+)?'
        . '(?:(?P<day>\d{1,2})\s+(?P<month>' . implode('|', $months) . ')\s+(?P<year>\d{4})|(?P<numDay>\d{1,2})\/(?P<numMonth>\d{1,2})\/(?P<numYear>\d{4}))?'
        . '\s*(?:[\x{2013}\x{2014}\-,.]\s*)?/u';

    // Paragraf pembuka adalah `<p>` pertama yang benar-benar berisi teks: badan
    // artikel CPNS dibuka galeri foto, dan dua berita terbaru membungkus tempatnya
    // sebagai `<p><strong>Natuna</strong> - `, jadi tempat di dalam tebal ikut dibaca.
    return (string) preg_replace_callback(
        '#<p(?P<attributes>\s[^>]*)?>(?:<(?:strong|b)>(?P<inner>[^<]+)</(?:strong|b)>)?(?P<text>[^<]+)#u',
        static function (array $paragraph) use ($dateline, $months): string {
            $attributes = $paragraph['attributes'] ?? '';
            $text = ($paragraph['inner'] ?? '') . $paragraph['text'];
            if (!preg_match($dateline, $text, $hit) || mb_strlen($hit[0]) > 60) {
                return $paragraph[0];
            }
            $textualDate = ($hit['day'] ?? '') !== '';
            $numericDate = ($hit['numDay'] ?? '') !== '';
            // Tanpa tanggal, hanya pisah tanda hubung yang boleh dianggap dateline:
            // "Natuna, kabupaten terluar, ..." adalah kalimat biasa, bukan kepala berita.
            if (!$textualDate && !$numericDate && !preg_match('/^[\x{2013}\x{2014}-]$/u', $hit['lead'])) {
                return $paragraph[0];
            }
            $dateLabel = '';
            if ($textualDate) {
                $dateLabel = (int) $hit['day'] . ' ' . $hit['month'] . ' ' . $hit['year'];
            } elseif ($numericDate) {
                $monthIndex = (int) $hit['numMonth'];
                if ($monthIndex < 1 || $monthIndex > 12) {
                    return $paragraph[0];
                }
                $dateLabel = (int) $hit['numDay'] . ' ' . $months[$monthIndex - 1] . ' ' . $hit['numYear'];
            }
            $rest = mb_substr($text, mb_strlen($hit[0]));
            if (preg_match('/^\p{Ll}/u', $rest)) {
                $rest = mb_strtoupper(mb_substr($rest, 0, 1)) . mb_substr($rest, 1);
            }
            $label = mb_strtoupper($hit['place']) . ($dateLabel !== '' ? ', ' . $dateLabel : '');
            return '<p' . $attributes . '><span class="editorial-article__dateline">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' &#8212;</span> ' . $rest;
        },
        $body,
        1
    );
}
