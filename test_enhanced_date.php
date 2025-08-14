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

    $day = null;
    $month = null;
    $year = null;

    // Pattern 1: "3 maret 2025"
    if (preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/', $dateString, $matches)) {
        $day = (int)$matches[1];
        $monthName = $matches[2];
        $year = (int)$matches[3];

        // Cari nomor bulan
        foreach ($monthMapping as $indonesianMonth => $monthNumber) {
            if (strpos($monthName, $indonesianMonth) !== false || $monthName === $indonesianMonth) {
                $month = $monthNumber;
                break;
            }
        }
    }
    // Pattern 2: "3-maret-2025"
    elseif (preg_match('/(\d{1,2})-(\w+)-(\d{4})/', $dateString, $matches)) {
        $day = (int)$matches[1];
        $monthName = $matches[2];
        $year = (int)$matches[3];

        // Cari nomor bulan
        foreach ($monthMapping as $indonesianMonth => $monthNumber) {
            if (strpos($monthName, $indonesianMonth) !== false || $monthName === $indonesianMonth) {
                $month = $monthNumber;
                break;
            }
        }
    }
    // Pattern 3: "maret 3, 2025" atau "maret 3 2025"
    elseif (preg_match('/(\w+)\s+(\d{1,2}),?\s+(\d{4})/', $dateString, $matches)) {
        $monthName = $matches[1];
        $day = (int)$matches[2];
        $year = (int)$matches[3];

        // Cari nomor bulan
        foreach ($monthMapping as $indonesianMonth => $monthNumber) {
            if (strpos($monthName, $indonesianMonth) !== false || $monthName === $indonesianMonth) {
                $month = $monthNumber;
                break;
            }
        }
    }

    // Validasi dan buat Carbon instance
    if ($month && $day >= 1 && $day <= 31 && $year >= 1900 && $year <= 2100) {
        try {
            return Carbon::create($year, $month, $day);
        } catch (\Exception $e) {
            return null;
        }
    }

    return null;
}

// Test cases
$testDates = [
    '3 maret 2025',
    '15 januari 2024',
    '3-maret-2025',
    'maret 3, 2025',
    'maret 3 2025',
    '31 desember 2023',
    '1-mei-2025',
    'februari 29, 2024'
];

echo "=== Test Enhanced Indonesian Date Parsing ===\n";
foreach ($testDates as $testDate) {
    $result = parseIndonesianDate($testDate);
    if ($result) {
        echo "✅ Input: '$testDate' -> Display: " . $result->format('d/m/Y') . " (DB: " . $result->format('Y-m-d') . ")\n";
    } else {
        echo "❌ Failed to parse: '$testDate'\n";
    }
}
