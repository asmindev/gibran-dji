<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Inventory Management') }} - @yield('title')</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Alpine.js x-cloak style -->
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="hidden md:flex md:w-64 md:flex-col">
            <div class="flex flex-col flex-grow bg-white shadow-xl border-r border-gray-200 overflow-y-auto">
                <!-- Logo -->
                <div class="flex items-center h-16 px-6 bg-primary">
                    <a href="{{ route('dashboard') }}" class="text-xl font-bold text-white">
                        📦 Inventory System
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-2">
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}"
                        class="@if(request()->routeIs('dashboard')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>

                    <!-- Master Data Section -->
                    <div class="space-y-1">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-2">
                            Master Data
                        </div>

                        <a href="{{ route('categories.index') }}"
                            class="@if(request()->routeIs('categories.*')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                </path>
                            </svg>
                            Kategori
                        </a>

                        <a href="{{ route('items.index') }}"
                            class="@if(request()->routeIs('items.*')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            Barang
                        </a>
                    </div>

                    <!-- Transactions Section -->
                    <div class="space-y-1">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-2">
                            Transactions
                        </div>

                        <a href="{{ route('incoming_items.index') }}"
                            class="@if(request()->routeIs('incoming_items.*')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                            </svg>
                            Barang Masuk
                        </a>

                        <a href="{{ route('outgoing_items.index') }}"
                            class="@if(request()->routeIs('outgoing_items.*')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H3"></path>
                            </svg>
                            Barang Keluar
                        </a>
                    </div>

                    <!-- Reports Section -->
                    {{-- <div class="space-y-1">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-2">
                            Reports
                        </div>

                        <a href="{{ route('reports.index') }}"
                            class="@if(request()->routeIs('reports.index')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Semua Laporan
                        </a>

                        <a href="{{ route('reports.stock') }}"
                            class="@if(request()->routeIs('reports.stock')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            Laporan Stok
                        </a>

                        <a href="{{ route('reports.incoming') }}"
                            class="@if(request()->routeIs('reports.incoming')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                            </svg>
                            Laporan Masuk
                        </a>

                        <a href="{{ route('reports.outgoing') }}"
                            class="@if(request()->routeIs('reports.outgoing')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H3"></path>
                            </svg>
                            Laporan Keluar
                        </a>

                        <a href="{{ route('reports.summary') }}"
                            class="@if(request()->routeIs('reports.summary')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            Laporan Ringkasan
                        </a>
                    </div> --}}

                    <!-- Analysis Section -->
                    <div class="space-y-1">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-2">
                            Analisis
                        </div>



                        <a href="{{ route('analysis.apriori-process') }}"
                            class="@if(request()->routeIs('analysis.apriori-process')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                </path>
                            </svg>
                            Proses Apriori
                        </a>
                    </div>

                    <!-- Prediction Section -->
                    <div class="space-y-1">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-2">
                            Prediksi
                        </div>

                        <a href="{{ route('predictions.index') }}"
                            class="@if(request()->routeIs('predictions.*')) bg-primary text-white @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                </path>
                            </svg>
                            Prediksi Stok
                        </a>



                    </div>
                </nav>

                <!-- Footer in Sidebar -->
                <div class="p-4 border-t border-gray-200">
                    <div class="text-xs text-gray-500 text-center">
                        © 2025 Inventory System
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Sidebar Overlay -->
        <div id="mobile-sidebar-overlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 md:hidden hidden">
            <div class="flex">
                <div class="w-64 bg-white shadow-xl h-full overflow-y-auto">
                    <!-- Mobile Logo -->
                    <div class="flex items-center justify-between h-16 px-6 bg-gradient-to-r from-blue-600 to-blue-700">
                        <a href="{{ route('dashboard') }}" class="text-xl font-bold text-white">
                            📦 Inventory
                        </a>
                        <button onclick="toggleMobileSidebar()" class="text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Mobile Navigation (same as desktop) -->
                    <nav class="flex-1 px-4 py-6 space-y-2">
                        <!-- Dashboard -->
                        <a href="{{ route('dashboard') }}"
                            class="@if(request()->routeIs('dashboard')) bg-blue-100 text-blue-700 @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200"
                            onclick="toggleMobileSidebar()">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                            </svg>
                            Dashboard
                        </a>

                        <!-- Master Data Section -->
                        <div class="space-y-1">
                            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-2">
                                Master Data
                            </div>

                            <a href="{{ route('categories.index') }}"
                                class="@if(request()->routeIs('categories.*')) bg-blue-100 text-blue-700 @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4"
                                onclick="toggleMobileSidebar()">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                                Kategori
                            </a>

                            <a href="{{ route('items.index') }}"
                                class="@if(request()->routeIs('items.*')) bg-blue-100 text-blue-700 @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4"
                                onclick="toggleMobileSidebar()">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                Barang
                            </a>
                        </div>

                        <!-- Transactions Section -->
                        <div class="space-y-1">
                            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-2">
                                Transactions
                            </div>

                            <a href="{{ route('incoming_items.index') }}"
                                class="@if(request()->routeIs('incoming_items.*')) bg-green-100 text-green-700 @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4"
                                onclick="toggleMobileSidebar()">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                </svg>
                                Barang Masuk
                            </a>

                            <a href="{{ route('outgoing_items.index') }}"
                                class="@if(request()->routeIs('outgoing_items.*')) bg-red-100 text-red-700 @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4"
                                onclick="toggleMobileSidebar()">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H3"></path>
                                </svg>
                                Barang Keluar
                            </a>
                        </div>

                        <!-- Reports Section -->
                        <div class="space-y-1">
                            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-2">
                                Reports
                            </div>

                            <a href="{{ route('reports.index') }}"
                                class="@if(request()->routeIs('reports.index')) bg-purple-100 text-purple-700 @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4"
                                onclick="toggleMobileSidebar()">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                Semua Laporan
                            </a>

                            <a href="{{ route('reports.stock') }}"
                                class="@if(request()->routeIs('reports.stock')) bg-purple-100 text-purple-700 @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4"
                                onclick="toggleMobileSidebar()">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10L8 4"></path>
                                </svg>
                                Laporan Stok
                            </a>

                            <a href="{{ route('reports.incoming') }}"
                                class="@if(request()->routeIs('reports.incoming')) bg-purple-100 text-purple-700 @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4"
                                onclick="toggleMobileSidebar()">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                </svg>
                                Laporan Masuk
                            </a>

                            <a href="{{ route('reports.outgoing') }}"
                                class="@if(request()->routeIs('reports.outgoing')) bg-purple-100 text-purple-700 @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4"
                                onclick="toggleMobileSidebar()">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H3"></path>
                                </svg>
                                Laporan Keluar
                            </a>

                            <a href="{{ route('reports.summary') }}"
                                class="@if(request()->routeIs('reports.summary')) bg-purple-100 text-purple-700 @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4"
                                onclick="toggleMobileSidebar()">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                Laporan Ringkasan
                            </a>
                        </div>

                        <!-- Analysis Section -->
                        <div class="space-y-1">
                            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 py-2">
                                Analisis
                            </div>


                            <a href="{{ route('analysis.apriori-process') }}"
                                class="@if(request()->routeIs('analysis.apriori-process')) bg-orange-100 text-orange-700 @else text-gray-600 hover:bg-gray-100 hover:text-gray-900 @endif flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ml-4"
                                onclick="toggleMobileSidebar()">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                    </path>
                                </svg>
                                Proses Apriori
                            </a>



                        </div>
                    </nav>
                </div>
                <div class="flex-1" onclick="toggleMobileSidebar()"></div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header with Mobile Menu Button -->
            <header class="bg-white shadow-sm border-b border-gray-200 md:hidden">
                <div class="flex items-center justify-between h-16 px-4">
                    <button onclick="toggleMobileSidebar()" class="text-gray-600 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">@yield('title', 'Inventory System')</h1>
                    <div class="w-6"></div> <!-- Spacer for centering -->
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 min-w-0 p-4">
                <div class="container mx-auto">
                    @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
                        role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                    @endif

                    @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                        role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <script>
        function toggleMobileSidebar() {
            const overlay = document.getElementById('mobile-sidebar-overlay');
            overlay.classList.toggle('hidden');
        }

        // Close mobile sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const overlay = document.getElementById('mobile-sidebar-overlay');
            const menuButton = event.target.closest('button[onclick="toggleMobileSidebar()"]');

            if (!menuButton && !overlay.classList.contains('hidden')) {
                const sidebar = overlay.querySelector('.w-64');
                if (!sidebar.contains(event.target)) {
                    overlay.classList.add('hidden');
                }
            }
        });
    </script>
</body>

</html>
