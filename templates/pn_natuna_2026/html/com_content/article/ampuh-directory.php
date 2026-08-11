<?php
/** Canonical AMPUH 2026 evidence directory renderer. */
defined('_JEXEC') or die;

if ((string) $item->alias !== 'ampuh-2026' || (int) $item->catid !== 9) {
    return false;
}

$datasetPath = JPATH_THEMES . '/pn_natuna_2026/data/ampuh-2026.json';
try {
    $source = file_get_contents($datasetPath);
    if ($source === false) {
        throw new RuntimeException('AMPUH dataset cannot be read.');
    }
    $directory = json_decode($source, true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    throw new RuntimeException('AMPUH dataset is invalid.', 0, $exception);
}

if (!is_array($directory) || !is_array($directory['gobis'] ?? null) || !is_string($directory['main_drive_url'] ?? null)) {
    throw new RuntimeException('AMPUH dataset has an invalid shape.');
}

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$normalized = static fn (string $value): string => preg_replace('/\s+/u', ' ', function_exists('mb_strtolower') ? mb_strtolower(trim($value), 'UTF-8') : strtolower(trim($value))) ?? '';
$gobiLabel = static function (array $gobi): string {
    $number = (float) ($gobi['number'] ?? 0);
    $formattedNumber = floor($number) === $number ? (string) (int) $number : rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    $name = trim((string) ($gobi['name'] ?? ''));
    $groupName = trim((string) preg_replace('/^\s*' . preg_quote($formattedNumber, '/') . '(?:\.0+)?\s*[-:–—]?\s*/u', '', $name));
    return 'GOBI ' . $formattedNumber . ($groupName !== '' ? ' · ' . $groupName : '');
};
$fileType = static function (string $file): string {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return match (true) {
        $extension === 'pdf' => 'PDF',
        in_array($extension, ['csv', 'xls', 'xlsx', 'ods'], true) => 'SHEET',
        in_array($extension, ['doc', 'docx', 'odt', 'rtf'], true) => 'WORD',
        in_array($extension, ['gif', 'jpeg', 'jpg', 'png', 'svg', 'webp'], true) => 'IMAGE',
        default => 'FILE',
    };
};
$withoutLeadingNumber = static function (string $number, string $title): string {
    $ordinal = str_contains($number, '.') ? substr($number, strrpos($number, '.') + 1) : $number;
    return (string) preg_replace('/^\s*(?:' . preg_quote($number, '/') . '|' . preg_quote($ordinal, '/') . ')\s*[.:-]?\s*/u', '', $title);
};
$isValidDriveUrl = static function (string $url): bool {
    $parts = parse_url($url);
    return $url !== '' && is_array($parts) && ($parts['scheme'] ?? '') === 'https' && ($parts['host'] ?? '') === 'drive.google.com';
};
$driveAction = static function (string $url, string $label) use ($escape, $isValidDriveUrl): void {
    if (!$isValidDriveUrl($url)) {
        echo '<span class="ampuh-directory__drive-unavailable">Tautan belum tersedia</span>';
        return;
    }
    echo '<a class="ampuh-directory__drive" href="' . $escape($url) . '" target="_blank" rel="noopener noreferrer">' . $escape($label) . '</a>';
};

$checklistNumbers = [];
$subchecklistCount = 0;
$fileCount = 0;
foreach ($directory['gobis'] as $gobi) {
    if (!is_array($gobi) || !is_array($gobi['checklists'] ?? null)) {
        throw new RuntimeException('AMPUH GOBI data has an invalid shape.');
    }
    foreach ($gobi['checklists'] as $checklist) {
        $checklistNumbers[(int) ($checklist['number'] ?? 0)] = true;
        if (!is_array($checklist) || !is_array($checklist['subchecklists'] ?? null)) {
            throw new RuntimeException('AMPUH checklist data has an invalid shape.');
        }
        $subchecklistCount += count($checklist['subchecklists']);
        foreach ($checklist['subchecklists'] as $subchecklist) {
            if (!is_array($subchecklist) || !is_array($subchecklist['files'] ?? null)) {
                throw new RuntimeException('AMPUH sub-checklist data has an invalid shape.');
            }
            $fileCount += count($subchecklist['files']);
        }
    }
}
?>
<article class="ampuh-directory" data-ampuh-directory>
  <header class="ampuh-directory__header">
    <div class="ampuh-directory__hero-main">
      <p class="ampuh-directory__kicker">Direktori Bukti</p>
      <h1><?php echo $escape((string) ($directory['title'] ?? 'AMPUH 2026 Checklist')); ?></h1>
      <p class="ampuh-directory__lede"><?php echo $escape((string) ($directory['summary'] ?? '')); ?></p>
      <?php $driveAction($directory['main_drive_url'], 'Buka Folder Utama AMPUH 2026'); ?>
    </div>
    <div class="ampuh-directory__hero-secondary"><span class="ampuh-directory__watermark" aria-hidden="true">2026</span><p>Pengadilan Negeri Natuna</p><strong>Arsip Publik</strong></div>
  </header>
  <section class="ampuh-directory__summary" aria-labelledby="ampuh-collection-index"><h2 id="ampuh-collection-index">Indeks Koleksi</h2><dl><div><dt>GOBI</dt><dd><?php echo count($directory['gobis']); ?></dd></div><div><dt>Checklist</dt><dd><?php echo count($checklistNumbers); ?></dd></div><div><dt>Sub-checklist</dt><dd><?php echo $subchecklistCount; ?></dd></div><div><dt>Dokumen</dt><dd><?php echo $fileCount; ?></dd></div></dl></section>
  <section class="ampuh-directory__tools" aria-label="Pencarian direktori"><div class="ampuh-directory__search"><label for="ampuh-directory-search">Cari dokumen AMPUH</label><input id="ampuh-directory-search" name="q" type="search" data-ampuh-search autocomplete="off" placeholder="Cari GOBI, checklist, atau nama dokumen…"></div><button type="button" data-ampuh-close-all>Tutup semua</button><div class="ampuh-directory__filter-nav"><button type="button" class="ampuh-directory__filter-arrow" data-ampuh-filter-prev aria-label="Gulir GOBI ke kiri">‹</button><div class="ampuh-directory__filter-window"><div class="ampuh-directory__filter-rail" data-ampuh-gobi-filter aria-label="Filter GOBI"><?php foreach ($directory['gobis'] as $gobi) : ?><button type="button" data-ampuh-filter-value="<?php echo $escape((string) (int) $gobi['number']); ?>" aria-pressed="false"><?php echo $escape($gobiLabel($gobi)); ?></button><?php endforeach; ?></div></div><button type="button" class="ampuh-directory__filter-arrow" data-ampuh-filter-next aria-label="Gulir GOBI ke kanan">›</button></div><div class="ampuh-directory__gobi-select"><label for="ampuh-gobi-select">Pilih GOBI</label><select id="ampuh-gobi-select" data-ampuh-gobi-select><option value="">Semua GOBI</option><?php foreach ($directory['gobis'] as $gobi) : ?><option value="<?php echo $escape((string) (int) $gobi['number']); ?>"><?php echo $escape($gobiLabel($gobi)); ?></option><?php endforeach; ?></select></div><div class="ampuh-directory__result-bar"><p data-ampuh-results aria-live="polite"></p><button type="button" data-ampuh-clear-search hidden>Bersihkan pencarian</button></div></section>
  <div class="ampuh-directory__tree">
    <?php foreach ($directory['gobis'] as $gobi) :
        $gobiId = 'ampuh-gobi-' . (int) $gobi['number'];
        $gobiDisplay = $gobiLabel($gobi);
        $gobiText = $normalized($gobiDisplay . ' ' . (string) $gobi['name']);
        $gobiChecklistCount = count($gobi['checklists']);
        $gobiSubchecklistCount = 0;
        $gobiFileCount = 0;
        foreach ($gobi['checklists'] as $countChecklist) {
            $gobiSubchecklistCount += count($countChecklist['subchecklists']);
            foreach ($countChecklist['subchecklists'] as $countSubchecklist) $gobiFileCount += count($countSubchecklist['files']);
        }
        $gobiParts = array_map('trim', explode('·', $gobiDisplay, 2));
        $gobiNumber = trim((string) preg_replace('/^GOBI\s+/u', '', $gobiParts[0]));
        $gobiTitle = $gobiParts[1] ?? '';
    ?>
      <section class="ampuh-directory__gobi" data-search-text="<?php echo $escape($gobiText); ?>" data-ampuh-gobi="<?php echo (int) $gobi['number']; ?>"><h2><button type="button" data-ampuh-toggle aria-expanded="false" aria-controls="<?php echo $gobiId; ?>"><span class="ampuh-directory__gobi-number"><small>GOBI</small><?php echo $escape($gobiNumber); ?></span><span class="ampuh-directory__gobi-title"><?php echo $escape($gobiTitle); ?><span class="ampuh-directory__meta"><?php echo $gobiChecklistCount; ?> checklist · <?php echo $gobiSubchecklistCount; ?> sub-checklist · <?php echo $gobiFileCount; ?> dokumen</span></span></button></h2><div id="<?php echo $gobiId; ?>" data-ampuh-panel hidden>
        <?php foreach ($gobi['checklists'] as $checklist) :
            $number = (string) $checklist['number'];
            $checklistId = $gobiId . '-checklist-' . (int) $checklist['number'];
            $checklistText = $normalized('checklist ' . $number . ' ' . (string) $checklist['title'] . ' ' . $gobiDisplay);
            $checklistSubchecklistCount = count($checklist['subchecklists']);
            $checklistFileCount = array_sum(array_map(static fn (array $sub): int => count($sub['files']), $checklist['subchecklists']));
        ?>
          <section class="ampuh-directory__checklist" data-ampuh-checklist="<?php echo $escape($number); ?>" data-search-text="<?php echo $escape($checklistText); ?>"><span class="ampuh-directory__check-number"><?php echo $escape($number); ?></span><h3><button type="button" data-ampuh-toggle aria-expanded="false" aria-controls="<?php echo $checklistId; ?>"><span class="ampuh-directory__check-title"><?php echo $escape($withoutLeadingNumber($number, (string) $checklist['title'])); ?></span><span class="ampuh-directory__meta"><?php echo $checklistSubchecklistCount; ?> sub-checklist · <?php echo $checklistFileCount; ?> dokumen</span></button></h3><?php $driveAction((string) ($checklist['drive_url'] ?? ''), 'Buka folder checklist'); ?><div id="<?php echo $checklistId; ?>" data-ampuh-panel hidden>
            <?php foreach ($checklist['subchecklists'] as $subchecklist) :
                $subNumber = (string) $subchecklist['number'];
                $subId = $checklistId . '-sub-' . preg_replace('/[^a-z0-9]+/i', '-', $subNumber);
                $subText = $normalized('sub-checklist ' . $subNumber . ' ' . (string) $subchecklist['title']);
            ?>
              <section class="ampuh-directory__subchecklist" data-ampuh-subchecklist="<?php echo $escape($subNumber); ?>" data-search-text="<?php echo $escape($subText); ?>"><span class="ampuh-directory__sub-number"><?php echo $escape($subNumber); ?></span><h4><button type="button" data-ampuh-toggle aria-expanded="false" aria-controls="<?php echo $escape($subId); ?>"><span class="ampuh-directory__sub-title"><?php echo $escape($withoutLeadingNumber($subNumber, (string) $subchecklist['title'])); ?></span></button></h4><?php if ($isValidDriveUrl((string) ($subchecklist['drive_url'] ?? ''))) : ?><span class="ampuh-directory__sub-drive"><?php $driveAction((string) $subchecklist['drive_url'], 'Buka folder sub-checklist'); ?></span><?php endif; ?><div id="<?php echo $escape($subId); ?>" data-ampuh-panel hidden><h5 class="ampuh-directory__files-heading">Daftar dokumen (<?php echo count($subchecklist['files']); ?>)</h5><ul class="ampuh-directory__files"><?php foreach ($subchecklist['files'] as $file) : ?><li data-ampuh-file-result data-file-type="<?php echo $escape($fileType($file)); ?>"><span class="ampuh-directory__file-name"><?php echo $escape($file); ?></span></li><?php endforeach; ?></ul></div></section>
            <?php endforeach; ?>
          </div></section>
        <?php endforeach; ?>
      </div></section>
    <?php endforeach; ?>
  </div>
</article>
<?php return true;
