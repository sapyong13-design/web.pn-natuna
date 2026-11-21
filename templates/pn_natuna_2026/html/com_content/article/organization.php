<?php
/** Interactive counterpart to the published PN Natuna organization poster. */
defined('_JEXEC') or die;

$org = require dirname(__DIR__, 3) . '/data/organization.php';
$people = $org['people'];
$escape = static fn ($text): string => htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$root = \Joomla\CMS\Uri\Uri::root(true);
// All profile links and portraits in the curated dataset are site-root relative.
foreach ($people as &$person) {
    foreach (['url', 'photo'] as $key) {
        if ($person[$key] !== '') {
            $person[$key] = $root . $person[$key];
        }
    }
}
unset($person);

$renderEntry = static function (array $assignment, bool $showRole) use ($people, $escape, $root): void {
    $id = $assignment['person'];
    $role = $assignment['role'];
    if ($id === null) : ?>
        <div class="org-entry org-entry--vacant" data-org-entry data-org-vacant data-org-role="<?= $escape($role) ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="3" stroke-dasharray="3 2"/><path d="M8 12h8"/></svg>
            <span class="org-person-text"><span class="org-role">Jabatan kosong</span><strong class="org-person-name"><?= $escape($role) ?></strong></span>
        </div>
    <?php else :
        $person = $people[$id];
        $link = $person['url'] !== '';
        ?>
        <<?= $link ? 'a href="' . $escape($person['url']) . '"' : 'button type="button"' ?> class="org-entry org-person" data-org-entry data-org-person="<?= $escape($id) ?>" data-org-role="<?= $escape($role) ?>">
            <span class="org-avatar">
                <?php $parts = preg_split('/\s+/u', preg_replace('/^Dr\.\s+/i', '', explode(',', $person['name'])[0])); ?>
                <span class="org-monogram" aria-hidden="true"><?= $escape(mb_substr($parts[0], 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '')) ?></span>
                <?php if ($person['photo'] !== '') : ?><img class="org-portrait" src="<?= $escape($root . '/images/profil/bagan/' . $id . '.webp') ?>" alt="" width="96" height="120" loading="lazy" decoding="async"><?php endif; ?>
            </span>
            <span class="org-person-text"><?php if ($showRole) : ?><span class="org-role"><?= $escape($role) ?></span><?php endif; ?><span class="org-person-name"><?= $escape($person['name']) ?></span></span>
            <svg class="org-entry-arrow" width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 10h11m-5-5 5 5-5 5"/></svg>
        </<?= $link ? 'a' : 'button' ?>>
    <?php endif;
};
?>
<article class="pn-org" aria-labelledby="pn-org-title" lang="id">
    <header class="org-mast">
        <div class="org-mast-text">
            <h1 id="pn-org-title" class="org-title">Struktur Organisasi</h1>
            <p class="org-court">Pengadilan Negeri Natuna Kelas II</p>
            <p class="org-intro">Telusuri susunan pimpinan, hakim, kepaniteraan, dan kesekretariatan. Pilih nama untuk melihat profil atau keterangan penugasan.</p>
            <a class="org-poster" href="<?= $escape($root) ?>/images/profil/struktur-organisasi-poster-baru.png"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M8 4H4v16h13v-4M11 3h9v9m0-9L9 14"/></svg>Lihat poster resmi</a>
        </div>
        <!-- Inset locator: simplified Bunguran coastline and court position from panjar-map.json. -->
        <svg class="org-map" viewBox="0 0 500 240" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false">
            <defs><pattern id="pn-org-grid" width="24" height="24" patternUnits="userSpaceOnUse"><path d="M0 24V0h24" fill="none" stroke="#9dd1bd" opacity=".16" stroke-width=".8"/></pattern></defs>
            <rect width="500" height="240" fill="url(#pn-org-grid)"/>
            <g fill="none" stroke="#9bc0a9" opacity=".35"><circle cx="257" cy="122" r="94"/><circle cx="257" cy="122" r="112" stroke-dasharray="1 6"/><path d="M257 2v22m0 196v17M134 122h22m202 0h21"/></g>
            <g transform="translate(112 -5) scale(.36)" fill="#2e6659" stroke="#a6c8b3" stroke-width="2.5" stroke-linejoin="round">
                <path d="M245 409l22 1 7 12 23-5 26 11 7 9 50 3 19 23 21 10 5 18 19 26-5 7-9-18-12-3-46-44-10-1-14 13-18 4-3 19-7-7-23 7 2 18-29 41 35 16-8-12 8 2 4 12 15 4 9 26 8-11 21-1 2-9 11-7 7 4 14-8 50-1 18 6 8-10-1-48 5-11 25-31 14 2 14-20 19-9 8-50 13-10-10-2 9-23-9 2-20 27 5-15-5-1 1-7-15-14v19l-9-1 1-22 18-7v5l18 7 2-21-12-14 18-24 1-10-8-1-16-18-28-11v-15l-49-39 5-27-14 2-17-7-31-37-8-52 17-29-13-1-9-12-11 4-10 28-23 32-63 52-14 3-14 34-16 11-23-2-18 29-37 11-10 9 4 6 13-2 12 17-4 37 38 40-12 14 2 7 26 14Z"/>
                <path d="M303 608l-13-12-14-1-6 21-12 1-9 25 11 3 6-14 19 10-3-12 18-6ZM217 459l-6-8 2-16-11-8-21 3 20 8-3 7 8 5-7 12 11 4ZM237 602l1-8 23 4 5-5v-11l-10-14-27-1Z"/>
            </g>
            <g fill="none" stroke="#efcb75"><circle cx="307" cy="108" r="7"/><path d="M314 108h23l17-18h77"/></g>
            <circle cx="307" cy="108" r="2.7" fill="#f9dc99"/><text x="354" y="84" fill="#f9dc99" font-size="12">PN Natuna</text>
            <text x="117" y="202" fill="#b9d8cd" font-size="11">Bunguran</text>
            <path d="M461 43V23l-3.5 6.5m3.5-6.5 3.5 6.5" stroke="#ebcf90" fill="none"/><text x="461" y="18" text-anchor="middle" fill="#ebcf90" font-size="9">U</text>
        </svg>
    </header>
    <div class="org-tools" data-org-tools role="search" aria-label="Pencarian dalam bagan">
        <label class="org-search-label" for="pn-org-search">Cari dalam bagan</label>
        <div class="org-search-box"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5"/></svg><input id="pn-org-search" data-org-search type="search" placeholder="Nama, jabatan, atau unit" autocomplete="off" aria-controls="pn-org-chart"><button type="button" data-org-clear hidden>Hapus</button></div>
        <button class="org-vacancy-filter" type="button" data-org-vacancy-filter aria-pressed="false" aria-controls="pn-org-chart">Jabatan kosong <span data-org-vacancy-count></span></button>
        <span class="org-status" data-org-status role="status" aria-live="polite"></span>
    </div>
    <div class="org-chart" id="pn-org-chart">
        <?php foreach ($org['branches'] as $branch) :
            $id = $branch['id'];
            $lead = $id === 'pimpinan';
            $judges = in_array($id, ['hakim', 'ad-hoc'], true);
            $division = !$lead && !$judges;
            $group = $lead ? 'section' : 'details';
            ?>
            <<?= $group ?> class="org-branch org-branch--<?= $escape($id) ?>" data-org-branch="<?= $escape($id) ?>" data-org-branch-label="<?= $escape($branch['label']) ?>"<?= $lead ? ' aria-labelledby="pn-org-lead"' : ' open' ?>>
                <?php if ($lead) : ?><h2 id="pn-org-lead" class="org-branch-title"><?= $escape($branch['label']) ?></h2>
                <?php else : ?><summary class="org-summary"><h2 class="org-branch-title"><?= $escape($branch['label']) ?></h2></summary><?php endif; ?>
                <div class="org-units">
                    <?php foreach ($branch['units'] as $index => $unit) :
                        $head = $division && $index === 0;
                        $showUnit = $division && !$head;
                        ?>
                        <div class="org-unit<?= $head ? ' org-unit--head' : '' ?>" data-org-unit="<?= $escape($unit['label']) ?>">
                            <?php if ($showUnit) : ?><h3 class="org-unit-title"><?= $escape($unit['label']) ?></h3><?php endif; ?>
                            <?php foreach ($unit['assignments'] as $assignment) :
                                $showRole = $lead || $head || (!$judges && $assignment['role'] !== $unit['label']) || ($judges && $assignment['role'] !== $branch['label'] && $assignment['role'] !== $unit['label']);
                                $renderEntry($assignment, $showRole);
                            endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </<?= $group ?>>
        <?php endforeach; ?>
    </div>
    <p class="org-empty" data-org-empty hidden>Tidak ada nama, jabatan, atau unit yang cocok. Coba kata kunci lain atau hapus pencarian.</p>
    <aside class="org-preview" id="pn-org-preview" data-org-preview role="dialog" aria-modal="false" aria-labelledby="pn-org-name" hidden>
        <div class="org-preview-head"><img class="org-preview-photo" data-org-photo alt="" width="68" height="84" hidden><div><h2 class="org-preview-name" id="pn-org-name" data-org-name></h2><p class="org-preview-context" data-org-context></p></div><button type="button" class="org-close" data-org-close aria-label="Tutup ringkasan profil"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m4 4 12 12M16 4 4 16"/></svg></button></div>
        <div class="org-preview-body"><h3 class="org-preview-label">Penugasan dalam bagan</h3><ul class="org-preview-roles" data-org-roles></ul><p class="org-preview-note" data-org-note hidden></p><a class="org-profile-link" data-org-link hidden>Lihat profil lengkap<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5"/></svg></a></div>
    </aside>
    <template data-org-data><?= $escape(json_encode($people, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR)) ?></template>
</article>
