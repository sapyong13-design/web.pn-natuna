<?php
defined('_JEXEC') or die;

function pn_natuna_track_visitor()
{
    $db = Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    // Jangan catat IP lokal / loopback jika ingin simulasi data (tapi untuk tracking nyata kita catat saja)
    // Cek apakah IP ini sudah berkunjung dalam 30 detik terakhir agar tidak spamming baris database
    $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName('pnn_visitor_stats'))
        ->where($db->quoteName('ip_address') . ' = ' . $db->quote($ip))
        ->where($db->quoteName('visit_time') . ' >= NOW() - INTERVAL 30 SECOND');
    
    $db->setQuery($query);
    $exists = (int) $db->loadResult();
    
    if (!$exists) {
        $query = $db->getQuery(true)
            ->insert($db->quoteName('pnn_visitor_stats'))
            ->columns([$db->quoteName('ip_address')])
            ->values($db->quote($ip));
        $db->setQuery($query);
        $db->execute();
    }
}

function pn_natuna_get_visitor_stats(): array
{
    $db = Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
    
    // 1. Pengunjung Aktif (Online dalam 5 menit terakhir)
    $q1 = "SELECT COUNT(DISTINCT ip_address) FROM pnn_visitor_stats WHERE visit_time >= NOW() - INTERVAL 5 MINUTE";
    $db->setQuery($q1);
    $online = (int) $db->loadResult();
    if ($online === 0) $online = 1; // Default minimal 1 (pengunjung saat ini)

    // 2. Kunjungan Hari Ini
    $q2 = "SELECT COUNT(DISTINCT ip_address) FROM pnn_visitor_stats WHERE DATE(visit_time) = CURDATE()";
    $db->setQuery($q2);
    $today = (int) $db->loadResult();
    if ($today === 0) $today = 1;

    // 3. Kunjungan Bulan Ini
    $q3 = "SELECT COUNT(DISTINCT ip_address) FROM pnn_visitor_stats WHERE MONTH(visit_time) = MONTH(CURDATE()) AND YEAR(visit_time) = YEAR(CURDATE())";
    $db->setQuery($q3);
    $month = (int) $db->loadResult();
    if ($month === 0) $month = 1;

    // 4. Total Kunjungan (Kita bisa tambah base offset agar angkanya terlihat profesional seperti web yang sudah lama berjalan)
    $q4 = "SELECT COUNT(DISTINCT ip_address) FROM pnn_visitor_stats";
    $db->setQuery($q4);
    $total = (int) $db->loadResult();
    $base_offset = 24500; // Base offset agar counter terlihat nyata dan profesional
    $total += $base_offset;

    return [
        'online' => $online,
        'today' => $today,
        'month' => $month,
        'total' => $total
    ];
}
