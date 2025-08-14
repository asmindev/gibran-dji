<?php

require 'vendor/autoload.php';

use Carbon\Carbon;

// Test parsing tanggal multilingual
function parseIndonesianDate($dateString) {
    $dateString = strtolower(trim($dateString));
    
    // Mapping bulan Indonesia dan Inggris ke nomor bulan
    $monthMapping = [
        // Indonesian months
        'januari' => 1, 'februari' => 2, 'maret' => 3,
        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
        'oktober' => 10, 'november' => 11, 'desember' => 12,
        // English months  
        'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
        'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
        'september' => 9, 'october' => 10, 'december' => 12,
        // Short English months
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
        'jun' => 6, 'jul' => 7, 'aug' => 8,
        'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12
    ];

    $day = null;
    $month = null;
    $year = null;

    // Pattern 1: "3 maret 2025" atau "1 july 2025"
    if (preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/', $dateString, $matches)) {
        $day = (int)$matches[1];
        $monthName = $matches[2];
        $year = (int)$matches[3];
        
        // Cari nomor bulan
        foreach ($monthMapping as $monthKey => $monthNumber) {
            if (strpos($monthName, $monthKey) !== false || $monthName === $monthKey) {
                $month = $monthNumber;
                break;
            }
        }
    }
    // Pattern 2: "3-maret-2025" atau "1-july-2025"
    elseif (preg_match('/(\d{1,2})-(\w+)-(\d{4})/', $dateString, $matches)) {
        $day = (int)$matches[1];
        $monthName = $matches[2];
        $year = (int)$matches[3];
        
        // Cari nomor bulan
        foreach ($monthMapping as $monthKey => $monthNumber) {
            if (strpos($monthName, $monthKey) !== false || $monthName === $monthKey) {
                $month = $monthNumber;
                break;
            }
        }
    }
    // Pattern 3: "maret 3, 2025" atau "july 1, 2025"
    elseif (preg_match('/(\w+)\s+(\d{1,2}),?\s+(\d{4})/', $dateString, $matches)) {
        $monthName = $matches[1];
        $day = (int)$matches[2];
        $year = (int)$matches[3];
        
        // Cari nomor bulan
        foreach ($monthMapping as $monthKey => $monthNumber) {
            if (strpos($monthName, $monthKey) !== false || $monthName === $monthKey) {
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

// Test cases dengan bahasa Indonesia dan Inggris
$testDates = [
    // Indonesian
    '3 maret 2025',
    '15 januari 2024', 
    '3-juli-2025',
    
    // English
    '1 july 2025',
    '25 december 2024',
    '15-march-2025',
    'july 1, 2025',
    'december 25 2024',
    
    // Short English
    '1 jul 2025',
    '25 dec 2024',
    'jul 1, 2025'
];

echo "=== Test Multilingual Date Parsing ===\n";
foreach ($testDates as $testDate) {
    $result = parseIndonesianDate($testDate);
    if ($result) {
        echo "✅ Input: '$testDate' -> Display: " . $result->format('d/m/Y') . " (DB: " . $result->format('Y-m-d') . ")\n";
    } else {
        echo "❌ Failed to parse: '$testDate'\n";
    }
}
