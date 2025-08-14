<?php

require 'vendor/autoload.php';

use Carbon\Carbon;

// Test parsing tanggal Indonesia
function parseIndonesianDate($dateString)
{
    $dateString = strtolower(trim($dateString));

    // Mapping bulan Indonesia ke nomor bulan
    $monthMapping = [
        'januari' => 1,
        'februari' => 2,
        'maret' => 3,
        'april' => 4,
        'mei' => 5,
        'juni' => 6,
        'juli' => 7,
        'agustus' => 8,
        'september' => 9,
        'oktober' => 10,
        'november' => 11,
        'desember' => 12
    ];

    // Pattern untuk menangkap tanggal, bulan, dan tahun
    $pattern = '/(\d{1,2})\s+(\w+)\s+(\d{4})/';

    if (preg_match($pattern, $dateString, $matches)) {
        $day = (int)$matches[1];
        $monthName = $matches[2];
        $year = (int)$matches[3];

        // Cari nomor bulan berdasarkan nama bulan
        $month = null;
        foreach ($monthMapping as $indonesianMonth => $monthNumber) {
            if (strpos($monthName, $indonesianMonth) !== false || $monthName === $indonesianMonth) {
                $month = $monthNumber;
                break;
            }
        }

        if ($month && $day >= 1 && $day <= 31 && $year >= 1900 && $year <= 2100) {
            return Carbon::create($year, $month, $day);
        }
    }

    return null;
}

// Test cases
$testDates = [
    '3 maret 2025',
    '15 januari 2024',
    '31 desember 2023',
    '1 mei 2025',
    '25 februari 2024'
];

echo "=== Test Indonesian Date Parsing ===\n";
foreach ($testDates as $testDate) {
    $result = parseIndonesianDate($testDate);
    if ($result) {
        echo "Input: $testDate -> Output: " . $result->format('d/m/Y') . " (DB: " . $result->format('Y-m-d') . ")\n";
    } else {
        echo "Failed to parse: $testDate\n";
    }
}
