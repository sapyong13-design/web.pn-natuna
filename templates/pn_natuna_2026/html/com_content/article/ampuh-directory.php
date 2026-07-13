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
$normalized = static fn (string $value): string => preg_replace('/\s+/u', ' ', mb_strtolower(trim($value), 'UTF-8')) ?? '';
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
$driveAction = static function (string $url, string $label) use ($escape): void {
    $parts = parse_url($url);
    if ($url === '' || !is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'drive.google.com') {
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
    <p>Direktori dokumen</p><h1><?php echo $escape((string) ($directory['title'] ?? 'AMPUH 2026 Checklist')); ?></h1>
    <p><?php echo $escape((string) ($directory['summary'] ?? '')); ?></p><?php $driveAction($directory['main_drive_url'], 'Buka Folder Utama AMPUH 2026'); ?>
  </header>
  <section class="ampuh-directory__summary" aria-label="Ringkasan inventaris"><dl><div><dt>GOBI</dt><dd><?php echo count($directory['gobis']); ?></dd></div><div><dt>Checklist</dt><dd><?php echo count($checklistNumbers); ?></dd></div><div><dt>Sub-checklist</dt><dd><?php echo $subchecklistCount; ?></dd></div><div><dt>Dokumen</dt><dd><?php echo $fileCount; ?></dd></div></dl></section>
  <section class="ampuh-directory__tools" aria-label="Pencarian direktori"><label for="ampuh-directory-search">Cari dokumen AMPUH</label><input id="ampuh-directory-search" type="search" data-ampuh-search autocomplete="off" placeholder="Cari GOBI, checklist, atau nama dokumen"><div data-ampuh-gobi-filter aria-label="Filter GOBI"><?php foreach ($directory['gobis'] as $gobi) : ?><button type="button" data-ampuh-filter-value="<?php echo $escape((string) (int) $gobi['number']); ?>" aria-pressed="false"><?php echo $escape($gobiLabel($gobi)); ?></button><?php endforeach; ?></div><button type="button" data-ampuh-close-all>Tutup semua</button><p data-ampuh-results aria-live="polite"></p></section>
  <div class="ampuh-directory__tree">
    <?php foreach ($directory['gobis'] as $gobi) : $gobiId = 'ampuh-gobi-' . (int) $gobi['number']; $gobiDisplay = $gobiLabel($gobi); $gobiText = $normalized($gobiDisplay . ' ' . (string) $gobi['name']); ?>
      <section class="ampuh-directory__gobi" data-search-text="<?php echo $escape($gobiText); ?>" data-ampuh-gobi="<?php echo (int) $gobi['number']; ?>"><?php $gobiChecklistCount = count($gobi['checklists']); $gobiSubchecklistCount = 0; $gobiFileCount = 0; foreach ($gobi['checklists'] as $countChecklist) { $gobiSubchecklistCount += count($countChecklist['subchecklists']); foreach ($countChecklist['subchecklists'] as $countSubchecklist) { $gobiFileCount += count($countSubchecklist['files']); } } ?><h2><button type="button" data-ampuh-toggle aria-expanded="false" aria-controls="<?php echo $gobiId; ?>"><?php echo $escape($gobiDisplay); ?> <span class="ampuh-directory__meta"><?php echo $gobiChecklistCount; ?> checklist · <?php echo $gobiSubchecklistCount; ?> sub-checklist · <?php echo $gobiFileCount; ?> dokumen</span></button></h2><div id="<?php echo $gobiId; ?>" data-ampuh-panel hidden>
        <?php foreach ($gobi['checklists'] as $checklist) : $checklistId = $gobiId . '-checklist-' . (int) $checklist['number']; $checklistText = $normalized((string) $checklist['title']); $checklistSubchecklistCount = count($checklist['subchecklists']); $checklistFileCount = 0; foreach ($checklist['subchecklists'] as $countSubchecklist) { $checklistFileCount += count($countSubchecklist['files']); } ?>
          <section class="ampuh-directory__checklist" data-search-text="<?php echo $escape($checklistText); ?>"><h3><button type="button" data-ampuh-toggle aria-expanded="false" aria-controls="<?php echo $checklistId; ?>"><?php echo $escape((string) $checklist['title']); ?> <span class="ampuh-directory__meta"><?php echo $checklistSubchecklistCount; ?> sub-checklist · <?php echo $checklistFileCount; ?> dokumen</span></button></h3><?php $driveAction((string) ($checklist['drive_url'] ?? ''), 'Buka folder checklist'); ?><div id="<?php echo $checklistId; ?>" data-ampuh-panel hidden>
            <?php foreach ($checklist['subchecklists'] as $subchecklist) : $subId = $checklistId . '-sub-' . preg_replace('/[^a-z0-9]+/i', '-', (string) $subchecklist['number']); $filesId = $subId . '-files'; $subText = $normalized((string) $subchecklist['title']); ?>
              <section class="ampuh-directory__subchecklist" data-ampuh-result data-search-text="<?php echo $escape($subText); ?>"><h4><button type="button" data-ampuh-toggle aria-expanded="false" aria-controls="<?php echo $escape($subId); ?>"><?php echo $escape((string) $subchecklist['number'] . '. ' . (string) $subchecklist['title']); ?></button></h4><?php $driveAction((string) ($subchecklist['drive_url'] ?? ''), 'Buka folder sub-checklist'); ?><div id="<?php echo $escape($subId); ?>" data-ampuh-panel hidden><h5><button type="button" data-ampuh-toggle aria-expanded="false" aria-controls="<?php echo $escape($filesId); ?>">Daftar dokumen (<?php echo count($subchecklist['files']); ?>)</button></h5><div id="<?php echo $escape($filesId); ?>" data-ampuh-panel hidden><ul><?php foreach ($subchecklist['files'] as $file) : ?><li data-ampuh-result data-search-text="<?php echo $escape($normalized((string) $file)); ?>"><span class="ampuh-directory__file-icon" aria-hidden="true"><?php echo $fileType((string) $file); ?></span><?php echo $escape((string) $file); ?></li><?php endforeach; ?></ul></div></div></section>
            <?php endforeach; ?>
          </div></section>
        <?php endforeach; ?>
      </div></section>
    <?php endforeach; ?>
  </div>
</article>
<?php return true;
