@extends('layouts.app')

@section('title', 'Preview Import Barang Keluar')

@section('content')
<div>
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Preview Import Barang Keluar</h1>
                <p class="text-gray-600 mt-1">Review data sebelum melakukan import</p>
            </div>
            <a href="{{ route('outgoing_items.import.form') }}"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                Kembali
            </a>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $totalRows }}</div>
                <div class="text-sm text-gray-600">Total Baris</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold {{ count($validationErrors) > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ count($validationErrors) }}
                </div>
                <div class="text-sm text-gray-600">Error Validasi</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold {{ count($stockErrors) > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ count($stockErrors) }}
                </div>
                <div class="text-sm text-gray-600">Error Stok</div>
            </div>
        </div>
    </div>

    @if($hasErrors)
    <!-- Error Summary -->
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Ditemukan kesalahan dalam data:</h3>
                <div class="mt-2 text-sm text-red-700">
                    @if(!empty($validationErrors))
                    <div class="mb-2">
                        <strong>Kesalahan Validasi:</strong>
                        <ul class="list-disc list-inside mt-1">
                            @foreach($validationErrors as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if(!empty($stockErrors))
                    <div>
                        <strong>Kesalahan Stok:</strong>
                        <ul class="list-disc list-inside mt-1">
                            @foreach($stockErrors as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Success Message -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">Data valid dan siap untuk diimport!</h3>
                <p class="text-sm text-green-700 mt-1">Semua data telah lolos validasi dan stok mencukupi.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Preview Data -->
    <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Preview Data (10 baris pertama)</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Baris
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode
                            Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama
                            Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tujuan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($preview as $row)
                    <tr class="{{ !empty($row['errors']) ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['row_number'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['data']['kode_barang'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $row['item'] ? $row['item']->item_name : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['data']['tanggal_keluar']
                            }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['data']['jumlah'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row['data']['tujuan'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(!empty($row['errors']))
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Error
                            </span>
                            <div class="text-xs text-red-600 mt-1">
                                {{ implode(', ', $row['errors']) }}
                            </div>
                            @else
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Valid
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end space-x-3">
        <a href="{{ route('outgoing_items.import.form') }}"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
            Upload File Lain
        </a>

        @if(!$hasErrors)
        <form action="{{ route('outgoing_items.import') }}" method="POST" enctype="multipart/form-data" class="inline"
            id="import-form">
            @csrf
            <input type="hidden" name="confirmed" value="1">
            <input type="hidden" name="file" value="{{ $uploadedFileName ?? '' }}">
            <button type="button" onclick="confirmAndSubmitImport()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                Lanjutkan Import
            </button>
        </form>
        @else
        <button disabled class="px-4 py-2 bg-gray-300 text-gray-500 rounded-lg font-medium cursor-not-allowed">
            Perbaiki Error Terlebih Dahulu
        </button>
        @endif
    </div>
</div>

<script>
    function confirmAndSubmitImport() {
    if (confirm('Yakin ingin melanjutkan import? Data akan ditambahkan ke database dan stok barang akan berkurang.')) {
        // Show loading state
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Memproses...';
        button.disabled = true;

        // Submit the form directly
        document.getElementById('import-form').submit();
    }
}
</script>
@endsection
