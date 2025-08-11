@extends('layouts.app')

@section('title', 'Import Barang Masuk')

@section('content')
<!-- Header Section -->
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Import Barang Masuk</h1>
            <p class="text-gray-600 mt-1">Upload file Excel atau CSV untuk menambah data barang masuk</p>
        </div>
        <a href="{{ route('incoming_items.index') }}"
            class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
            Kembali
        </a>
    </div>
</div>


<div class="bg-white shadow rounded-lg p-6">
    <!-- Instructions -->
    <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <h3 class="text-lg font-medium text-blue-900 mb-2">Panduan Import</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>• File harus berformat Excel (.xlsx) atau CSV (.csv)</li>
            <li>• Ukuran file maksimal 10MB</li>
            <li>• Pastikan format kolom sesuai dengan template</li>
            <li>• ID Transaksi wajib diisi untuk setiap baris</li>
            <li>• Nama barang harus sudah ada dalam sistem</li>
            <li>• Stok akan otomatis bertambah setelah import</li>
            <li>• Data akan langsung diimport setelah upload</li>
        </ul>
    </div>

    <!-- Download Template -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Template File</h3>
        <p class="text-sm text-gray-600 mb-4">Download template untuk memastikan format data yang benar:</p>
        <a href="{{ route('incoming_items.template') }}"
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Download Template
        </a>
    </div>

    <!-- Upload Form -->
    <form id="import-form" action="{{ route('incoming_items.import') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-6">
            <label for="file" class="block text-sm font-medium text-gray-700 mb-2">
                File Import <span class="text-red-500">*</span>
            </label>
            <div
                class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                <input type="file" name="file" id="file" accept=".xlsx,.csv" required class="hidden"
                    onchange="handleFileSelect(this)">
                <label for="file" class="cursor-pointer">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path
                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="mt-4">
                        <span class="text-blue-600 font-medium">Klik untuk pilih file</span>
                        <span class="text-gray-500"> atau drag and drop</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Excel (.xlsx) atau CSV (.csv) hingga 10MB</p>
                </label>
            </div>
            <div id="file-name" class="mt-2 text-sm text-gray-600 hidden"></div>
            <div id="file-info" class="mt-2 text-sm text-blue-600 hidden"></div>
            @error('file')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

            @if(session('import_errors'))
            <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-red-800">
                            Import Gagal - {{ session('error_count') }} Error Ditemukan
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                            @if(isset(session('import_errors')['not_found']))
                            <div class="mb-3">
                                <h4 class="font-medium text-red-800">Nama Barang Tidak Ditemukan:</h4>
                                <ul class="mt-1 list-disc list-inside">
                                    @foreach(session('import_errors')['not_found'] as $error)
                                    <li>Baris {{ $error['row'] }}: {{ $error['error'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            @if(isset(session('import_errors')['validation']))
                            <div class="mb-3">
                                <h4 class="font-medium text-red-800">Error Validasi:</h4>
                                <ul class="mt-1 list-disc list-inside">
                                    @foreach(session('import_errors')['validation'] as $error)
                                    <li>Baris {{ $error['row'] }}: {{ $error['error'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        </div>
                        <div class="mt-3">
                            <div class="bg-blue-50 border border-blue-200 rounded p-2">
                                <p class="text-xs text-blue-700">
                                    <strong>Tips:</strong>
                                    @if(isset(session('import_errors')['not_found']))
                                    Pastikan nama barang sudah terdaftar di master data.
                                    @endif
                                    Download template untuk format yang benar.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- File Preview Section -->
        <div id="file-preview" class="mb-6 hidden">
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Preview Data (5 baris pertama)</h3>
                    <p class="text-sm text-gray-600 mt-1">Periksa data sebelum import. Data akan langsung diproses saat
                        submit.</p>
                </div>
                <div class="p-4">
                    <div id="preview-content" class="overflow-x-auto">
                        <!-- Preview table will be inserted here -->
                    </div>
                    <div id="preview-stats" class="mt-3 text-sm text-gray-600">
                        <!-- File stats will be shown here -->
                    </div>
                    <div id="preview-errors" class="mt-3 hidden">
                        <!-- Validation errors will be shown here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Error Section -->
        <div id="import-error-section" class="mb-6 hidden">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-red-800">Import Gagal</h3>
                        <div id="import-error-content" class="mt-2 text-sm text-red-700">
                            <!-- Error content will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('incoming_items.index') }}"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                Batal
            </a>
            <button type="button" id="submit-btn" onclick="submitImport()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                Import Data
            </button>
            <button type="button" id="submit-with-errors" onclick="submitWithWarning()"
                class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors hidden">
                Lanjutkan Meski Ada Error
            </button>
        </div>
    </form>
</div>

<!-- Format Example -->
<div class="mt-6 bg-white shadow rounded-lg p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Contoh Format Data</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID Transaksi</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Transaksi</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama Barang</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Harga Satuan</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <td class="px-4 py-2 text-sm">1</td>
                    <td class="px-4 py-2 text-sm">TRX20250810001</td>
                    <td class="px-4 py-2 text-sm">10/08/2025</td>
                    <td class="px-4 py-2 text-sm">Barang A</td>
                    <td class="px-4 py-2 text-sm">Kategori A</td>
                    <td class="px-4 py-2 text-sm">50</td>
                    <td class="px-4 py-2 text-sm">15000</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 text-sm">2</td>
                    <td class="px-4 py-2 text-sm">TRX20250810002</td>
                    <td class="px-4 py-2 text-sm">10/08/2025</td>
                    <td class="px-4 py-2 text-sm">Barang B</td>
                    <td class="px-4 py-2 text-sm">Kategori B</td>
                    <td class="px-4 py-2 text-sm">25</td>
                    <td class="px-4 py-2 text-sm">8000</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>

@push('scripts')
<!-- Include SheetJS library for Excel reading -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    let fileData = null;
let previewData = [];

function handleFileSelect(input) {
    const fileNameDiv = document.getElementById('file-name');
    const fileInfoDiv = document.getElementById('file-info');
    const previewDiv = document.getElementById('file-preview');

    if (input.files.length > 0) {
        const file = input.files[0];
        fileNameDiv.textContent = 'File terpilih: ' + file.name;
        fileNameDiv.classList.remove('hidden');

        // Show file info
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        fileInfoDiv.textContent = `Ukuran: ${fileSize} MB | Type: ${file.type || 'Unknown'}`;
        fileInfoDiv.classList.remove('hidden');

        // Read and preview file
        readAndPreviewFile(file);
    } else {
        fileNameDiv.classList.add('hidden');
        fileInfoDiv.classList.add('hidden');
        previewDiv.classList.add('hidden');
    }
}

function readAndPreviewFile(file) {
    const reader = new FileReader();
    const fileName = file.name.toLowerCase();

    reader.onload = function(e) {
        try {
            let data;

            if (fileName.endsWith('.csv')) {
                data = parseCSV(e.target.result);
            } else if (fileName.endsWith('.xlsx') || fileName.endsWith('.xls')) {
                const workbook = XLSX.read(e.target.result, {type: 'binary'});
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                data = XLSX.utils.sheet_to_json(firstSheet, {header: 1, defval: ''});
            } else {
                throw new Error('Format file tidak didukung');
            }

            // Ensure data is properly formatted
            if (!Array.isArray(data)) {
                throw new Error('Data file tidak dapat dibaca sebagai array');
            }

            // Clean up data - ensure all rows are arrays
            data = data.map(row => {
                if (!Array.isArray(row)) {
                    return [];
                }
                return row.map(cell => cell !== null && cell !== undefined ? String(cell) : '');
            });

            displayPreview(data);

        } catch (error) {
            console.error('Error reading file:', error);
            showError('Gagal membaca file: ' + error.message);
        }
    };

    reader.onerror = function() {
        showError('Gagal membaca file: Error saat membaca file');
    };

    if (fileName.endsWith('.csv')) {
        reader.readAsText(file);
    } else {
        reader.readAsBinaryString(file);
    }
}

function parseCSV(text) {
    const lines = text.split('\n');
    const result = [];

    for (let line of lines) {
        if (line.trim()) {
            // Simple CSV parsing - handle quoted fields
            const fields = line.split(',').map(field => {
                const trimmed = field.trim().replace(/^["']|["']$/g, '');
                return trimmed || ''; // Ensure we return empty string if undefined
            });
            result.push(fields);
        }
    }

    return result;
}

// Function to convert Excel serial number to date
function excelSerialToDate(serial) {
    // Excel serial date starts from January 1, 1900
    // But Excel incorrectly considers 1900 as a leap year, so we adjust
    const excelEpoch = new Date(1900, 0, 1);
    const days = parseInt(serial) - 1; // Subtract 1 because Excel starts counting from 1

    // Adjust for Excel's leap year bug (day 60 = Feb 29, 1900 which doesn't exist)
    const adjustedDays = days > 59 ? days - 1 : days;

    const resultDate = new Date(excelEpoch.getTime() + (adjustedDays * 24 * 60 * 60 * 1000));

    // Format as DD/MM/YYYY
    const day = String(resultDate.getDate()).padStart(2, '0');
    const month = String(resultDate.getMonth() + 1).padStart(2, '0');
    const year = resultDate.getFullYear();

    return `${day}/${month}/${year}`;
}

// Function to check if a value is an Excel serial number for date
function isExcelDateSerial(value) {
    // Check if it's a number and within reasonable date range (1900-2100)
    const num = parseFloat(value);
    return !isNaN(num) && num > 1 && num < 73415; // 73415 is roughly year 2100
}

function displayPreview(data) {
    if (!data || data.length === 0) {
        showError('File kosong atau tidak dapat dibaca');
        return;
    }

    fileData = data;
    previewData = data.slice(0, 6); // Header + 5 rows

    const previewDiv = document.getElementById('file-preview');
    const contentDiv = document.getElementById('preview-content');
    const statsDiv = document.getElementById('preview-stats');
    const errorsDiv = document.getElementById('preview-errors');

    // Build preview table
    let tableHTML = '<table class="min-w-full divide-y divide-gray-200">';

    // Header
    let headerMapping = {};
    if (previewData.length > 0) {
        tableHTML += '<thead class="bg-gray-50"><tr>';
        const headers = previewData[0] || [];
        headerMapping = getHeaderMapping(headers);
        // Expected headers in order - these should match exactly
        const expectedHeaders = ["NO","ID TRANSAKSI", "TANGGAL TRANSAKSI", "NAMA BARANG", "KATEGORI", "JUMLAH", "HARGA SATUAN"];

        headers.forEach((header, index) => {
            const headerStr = header ? String(header).trim() : '';
            // Check if this header matches any of the expected headers
            const matchedHeader = expectedHeaders.find(expected =>
                headerStr.toLowerCase() === expected.toLowerCase() ||
                headerStr.toLowerCase().includes(expected.toLowerCase()) ||
                expected.toLowerCase().includes(headerStr.toLowerCase())
            );

            const isCorrect = matchedHeader !== undefined;
            const bgClass = isCorrect ? 'bg-green-50' : 'bg-red-50';

            tableHTML += `<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase ${bgClass}" title="${isCorrect ? 'Header valid: ' + matchedHeader : 'Header tidak dikenali'}">${headerStr || 'Kolom ' + (index + 1)}</th>`;
        });
        tableHTML += '</tr></thead>';
    }

    // Data rows
    tableHTML += '<tbody class="bg-white divide-y divide-gray-200">';
    for (let i = 1; i < previewData.length && i <= 6; i++) {
        const row = previewData[i] || [];
        const hasErrors = validateRow(row, i, headerMapping);

        tableHTML += `<tr class="${hasErrors ? 'bg-red-50' : 'hover:bg-gray-50'}">`;
        row.forEach((cell, cellIndex) => {
            let cellValue = cell ? String(cell) : '';
            let cellClass = 'px-4 py-2 text-sm text-gray-900';

            // Check if this is a date column and convert Excel serial if needed
            const tanggalIndex = headerMapping["TANGGAL TRANSAKSI"];
            if (cellIndex === tanggalIndex && cellValue && isExcelDateSerial(cellValue)) {
                cellValue = excelSerialToDate(cellValue);
            }

            // Basic validation styling using header mapping
            const trimmedValue = cellValue.trim();

            // Check if this cell index corresponds to required fields
            const idTransaksiIndex = headerMapping["ID TRANSAKSI"];
            const namaBarangIndex = headerMapping["NAMA BARANG"];
            const jumlahIndex = headerMapping["JUMLAH"];

            if (cellIndex === idTransaksiIndex && !trimmedValue) cellClass += ' bg-red-100'; // ID Transaksi required
            if (cellIndex === namaBarangIndex && !trimmedValue) cellClass += ' bg-red-100'; // Nama barang required
            if (cellIndex === tanggalIndex && !trimmedValue) cellClass += ' bg-red-100'; // Tanggal required
            if (cellIndex === jumlahIndex && (!trimmedValue || isNaN(trimmedValue) || parseFloat(trimmedValue) <= 0)) cellClass += ' bg-red-100'; // Jumlah required & numeric

            tableHTML += `<td class="${cellClass}">${cellValue}</td>`;
        });
        tableHTML += '</tr>';
    }
    tableHTML += '</tbody></table>';

    contentDiv.innerHTML = tableHTML;

    // Show stats
    const totalRows = data.length - 1; // Exclude header
    statsDiv.innerHTML = `Total ${totalRows} baris data ditemukan (menampilkan 5 baris pertama)`;

    // Validate and show errors
    const errors = validateAllData(data, headerMapping);
    if (errors.length > 0) {
        let errorHTML = '<div class="bg-red-50 border border-red-200 rounded-lg p-3">';
        errorHTML += '<h4 class="text-sm font-medium text-red-800 mb-2">Masalah ditemukan:</h4>';
        errorHTML += '<ul class="text-sm text-red-700 list-disc list-inside">';
        errors.slice(0, 5).forEach(error => {
            errorHTML += `<li>${error}</li>`;
        });
        if (errors.length > 5) {
            errorHTML += `<li>... dan ${errors.length - 5} masalah lainnya</li>`;
        }
        errorHTML += '</ul></div>';
        errorsDiv.innerHTML = errorHTML;
        errorsDiv.classList.remove('hidden');

        // Show alternative submit button
        document.getElementById('submit-btn').textContent = 'Import Data (Ada Error)';
        document.getElementById('submit-with-errors').classList.remove('hidden');
    } else {
        errorsDiv.classList.add('hidden');
        document.getElementById('submit-btn').textContent = 'Import Data';
        document.getElementById('submit-with-errors').classList.add('hidden');
    }

    previewDiv.classList.remove('hidden');
}

function getHeaderMapping(headers) {
    const expectedHeaders = ["NO","ID TRANSAKSI", "TANGGAL TRANSAKSI", "NAMA BARANG", "KATEGORI", "JUMLAH", "HARGA SATUAN"];
    const mapping = {};

    expectedHeaders.forEach(expected => {
        const headerIndex = headers.findIndex(header => {
            const headerStr = header ? String(header).trim() : '';
            return headerStr.toLowerCase() === expected.toLowerCase() ||
                   headerStr.toLowerCase().includes(expected.toLowerCase()) ||
                   expected.toLowerCase().includes(headerStr.toLowerCase());
        });

        if (headerIndex !== -1) {
            mapping[expected] = headerIndex;
        }
    });

    return mapping;
}

function validateRow(row, rowIndex, headerMapping) {
    let hasErrors = false;

    // Ensure row exists and has elements
    if (!row || !Array.isArray(row)) {
        return true;
    }

    // ID Transaksi required
    const idTransaksiIndex = headerMapping["ID TRANSAKSI"];
    if (idTransaksiIndex !== undefined) {
        const idTransaksi = row[idTransaksiIndex] ? String(row[idTransaksiIndex]).trim() : '';
        if (!idTransaksi) {
            hasErrors = true;
        }
    } else {
        hasErrors = true; // Required column missing
    }

    // Nama barang required
    const namaBarangIndex = headerMapping["NAMA BARANG"];
    if (namaBarangIndex !== undefined) {
        const namaBarang = row[namaBarangIndex] ? String(row[namaBarangIndex]).trim() : '';
        if (!namaBarang) {
            hasErrors = true;
        }
    } else {
        hasErrors = true; // Required column missing
    }

    // Tanggal required
    const tanggalIndex = headerMapping["TANGGAL TRANSAKSI"];
    if (tanggalIndex !== undefined) {
        let tanggal = row[tanggalIndex] ? String(row[tanggalIndex]).trim() : '';

        // Convert Excel serial number to date if needed
        if (tanggal && isExcelDateSerial(tanggal)) {
            tanggal = excelSerialToDate(tanggal);
        }

        if (!tanggal) {
            hasErrors = true;
        }
    } else {
        hasErrors = true; // Required column missing
    }

    // Jumlah required and numeric
    const jumlahIndex = headerMapping["JUMLAH"];
    if (jumlahIndex !== undefined) {
        const jumlah = row[jumlahIndex] ? String(row[jumlahIndex]).trim() : '';
        if (!jumlah || isNaN(jumlah) || parseFloat(jumlah) <= 0) {
            hasErrors = true;
        }
    } else {
        hasErrors = true; // Required column missing
    }

    return hasErrors;
}

function validateAllData(data, headerMapping = {}) {
    const errors = [];

    if (!data || data.length < 2) {
        errors.push('File harus memiliki header dan minimal 1 baris data');
        return errors;
    }

    // Check required headers using mapping
    const requiredHeaders = ["ID TRANSAKSI", "NAMA BARANG", "TANGGAL TRANSAKSI", "JUMLAH"];
    const missingHeaders = requiredHeaders.filter(header =>
        headerMapping[header] === undefined
    );

    if (missingHeaders.length > 0) {
        errors.push(`Header berikut wajib ada: ${missingHeaders.join(', ')}`);
    }

    // Validate data rows
    for (let i = 1; i < data.length; i++) {
        const row = data[i] || [];

        // ID Transaksi validation
        const idTransaksiIndex = headerMapping["ID TRANSAKSI"];
        if (idTransaksiIndex !== undefined) {
            const idTransaksi = row[idTransaksiIndex] ? String(row[idTransaksiIndex]).trim() : '';
            if (!idTransaksi) {
                errors.push(`Baris ${i}: ID Transaksi tidak boleh kosong`);
            }
        }

        // Nama barang validation
        const namaBarangIndex = headerMapping["NAMA BARANG"];
        if (namaBarangIndex !== undefined) {
            const namaBarang = row[namaBarangIndex] ? String(row[namaBarangIndex]).trim() : '';
            if (!namaBarang) {
                errors.push(`Baris ${i}: Nama barang tidak boleh kosong`);
            }
        }

        // Tanggal validation
        const tanggalIndex = headerMapping["TANGGAL TRANSAKSI"];
        if (tanggalIndex !== undefined) {
            let tanggal = row[tanggalIndex] ? String(row[tanggalIndex]).trim() : '';

            // Convert Excel serial number to date if needed
            if (tanggal && isExcelDateSerial(tanggal)) {
                tanggal = excelSerialToDate(tanggal);
            }

            if (!tanggal) {
                errors.push(`Baris ${i}: Tanggal transaksi tidak boleh kosong`);
            }
        }

        // Jumlah validation
        const jumlahIndex = headerMapping["JUMLAH"];
        if (jumlahIndex !== undefined) {
            const jumlah = row[jumlahIndex] ? String(row[jumlahIndex]).trim() : '';
            if (!jumlah) {
                errors.push(`Baris ${i}: Jumlah tidak boleh kosong`);
            } else if (isNaN(jumlah) || parseFloat(jumlah) <= 0) {
                errors.push(`Baris ${i}: Jumlah harus berupa angka positif`);
            }
        }
    }

    return errors;
}

function showError(message) {
    const previewDiv = document.getElementById('file-preview');
    const contentDiv = document.getElementById('preview-content');

    contentDiv.innerHTML = `
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error</h3>
                    <p class="text-sm text-red-700 mt-1">${message}</p>
                </div>
            </div>
        </div>
    `;

    previewDiv.classList.remove('hidden');
}

// Legacy function for backward compatibility
function updateFileName(input) {
    handleFileSelect(input);
}

function showLoading() {
    const submitBtn = document.getElementById('submit-btn');
    const submitWithErrorsBtn = document.getElementById('submit-with-errors');

    submitBtn.textContent = 'Memproses Import...';
    submitBtn.disabled = true;
    submitWithErrorsBtn.disabled = true;
}

function hideLoading() {
    const submitBtn = document.getElementById('submit-btn');
    const submitWithErrorsBtn = document.getElementById('submit-with-errors');

    submitBtn.textContent = 'Import Data';
    submitBtn.disabled = false;
    submitWithErrorsBtn.disabled = false;
}

function submitImport() {
    const form = document.getElementById('import-form');
    const fileInput = document.getElementById('file');

    if (!fileInput.files.length) {
        alert('Silakan pilih file terlebih dahulu');
        return;
    }

    showLoading();
    hideImportError();

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();

        if (data.success) {
            // Redirect to index (flash message already set in session)
            window.location.href = data.redirect_url;
        } else {
            // Show errors
            showImportError(data.errors, data.error_count);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        showImportError([{
            type: 'general',
            message: 'Terjadi kesalahan saat memproses import. Silakan coba lagi.'
        }], 1);
    });
}

function showImportError(errors, errorCount) {
    const errorSection = document.getElementById('import-error-section');
    const errorContent = document.getElementById('import-error-content');

    let errorHTML = `<p class="font-medium mb-2">Ditemukan ${errorCount} kesalahan:</p>`;

    if (errors.not_found && errors.not_found.length > 0) {
        errorHTML += '<div class="mb-3">';
        errorHTML += '<h4 class="font-medium text-red-800">Nama Barang Tidak Ditemukan:</h4>';
        errorHTML += '<ul class="mt-1 list-disc list-inside text-sm">';
        errors.not_found.forEach(error => {
            errorHTML += `<li>Baris ${error.row}: ${error.error}</li>`;
        });
        errorHTML += '</ul></div>';
    }

    if (errors.validation && errors.validation.length > 0) {
        errorHTML += '<div class="mb-3">';
        errorHTML += '<h4 class="font-medium text-red-800">Error Validasi:</h4>';
        errorHTML += '<ul class="mt-1 list-disc list-inside text-sm">';
        errors.validation.forEach(error => {
            errorHTML += `<li>Baris ${error.row}: ${error.error}</li>`;
        });
        errorHTML += '</ul></div>';
    }

    if (errors.general) {
        errorHTML += `<div class="text-sm">${errors.general}</div>`;
    }

    errorHTML += '<div class="mt-3 p-2 bg-blue-50 border border-blue-200 rounded">';
    errorHTML += '<p class="text-xs text-blue-700">';
    errorHTML += '<strong>Tips:</strong> ';
    if (errors.not_found && errors.not_found.length > 0) {
        errorHTML += 'Pastikan nama barang sudah terdaftar di master data. ';
    }
    errorHTML += 'Download template untuk format yang benar.';
    errorHTML += '</p></div>';

    errorContent.innerHTML = errorHTML;
    errorSection.classList.remove('hidden');
}

function hideImportError() {
    const errorSection = document.getElementById('import-error-section');
    errorSection.classList.add('hidden');
}

function submitWithWarning() {
    if (confirm('File mengandung error, tetapi Anda dapat melanjutkan dengan import. Data dengan error akan dilewati. Lanjutkan?')) {
        submitImport();
    }
}

// Drag and drop functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.querySelector('.border-dashed');
    const fileInput = document.getElementById('file');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    dropZone.addEventListener('drop', handleDrop, false);

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight() {
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    }

    function unhighlight() {
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect(fileInput);
        }
    }
});
</script>
@endpush

@endsection
