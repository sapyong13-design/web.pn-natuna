-- Tanggal mulai penghitungan kunjungan.
--
-- Penghitung akan ditampilkan di kaki situs, dan angka tanpa keterangan sejak kapan ia
-- dihitung adalah angka yang tidak bisa ditelusuri - persis yang dilarang prinsip situs
-- ini. Tanggalnya tidak boleh diambil dari `MIN(bucket_start)` karena ember rinci dibuang
-- setelah 32 hari, sehingga labelnya akan merayap maju dan berbohong pelan-pelan.
--
-- Kolom ini menyimpannya sekali. Nilai awal diambil dari ember terlama yang masih ada
-- saat migrasi berjalan; bila tabelnya kosong, dipakai waktu migrasi itu sendiri.
--
-- Catatan teknis: `ADD COLUMN IF NOT EXISTS` adalah sintaks MariaDB dan ditolak MySQL 8.4
-- yang dipakai proyek ini. Idempotensinya dibangun lewat information_schema plus prepared
-- statement - pola pertama di repo ini, jadi ditulis eksplisit agar bisa ditiru.
SET @counter_start := (SELECT MIN(bucket_start) FROM #__visitor_aggregates);

SET @has_column := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = '#__visitor_totals'
      AND COLUMN_NAME = 'counting_since'
);
SET @add_column := IF(
    @has_column = 0,
    'ALTER TABLE #__visitor_totals ADD COLUMN counting_since DATETIME NULL DEFAULT NULL AFTER bucket_hits',
    'DO 1'
);
PREPARE add_counting_since FROM @add_column;
EXECUTE add_counting_since;
DEALLOCATE PREPARE add_counting_since;

INSERT INTO #__visitor_totals (counter_id, total_hits, current_bucket, bucket_hits, counting_since)
SELECT 1, 0, NULL, 0, COALESCE(@counter_start, UTC_TIMESTAMP())
WHERE NOT EXISTS (SELECT 1 FROM (SELECT 1 FROM #__visitor_totals WHERE counter_id = 1 LIMIT 1) existing);

UPDATE #__visitor_totals
SET counting_since = COALESCE(@counter_start, UTC_TIMESTAMP())
WHERE counter_id = 1 AND counting_since IS NULL;
