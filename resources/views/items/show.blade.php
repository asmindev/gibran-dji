@extends('layouts.app')

@section('title', 'Item Details')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-primary text-white">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Item Details</h1>
                <div class="flex space-x-2">
                    <a href="{{ route('items.edit', $item) }}"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        Edit Barang
                    </a>
                    <a href="{{ route('items.index') }}"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Item Image -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        @if($item->image)
                        <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}"
                            class="w-full max-w-sm mx-auto rounded-lg shadow-md">
                        @else
                        <div class="w-full max-w-sm mx-auto bg-gray-200 rounded-lg flex items-center justify-center"
                            style="height: 300px;">
                            <svg class="w-16 h-16 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500 mt-4">No image available</p>
                        @endif
                    </div>
                </div>

                <!-- Item Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $item->name }}</h2>
                        <div class="flex items-center space-x-4 mb-4">
                            <span
                                class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $item->category->name }}
                            </span>
                            {{-- <span class="text-gray-600">SKU: {{ $item->sku }}</span> --}}
                        </div>

                        @if($item->description)
                        <p class="text-gray-700 bg-gray-50 p-4 rounded-md">{{ $item->description }}</p>
                        @endif
                    </div>

                    <!-- Stock Information -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white border border-gray-300 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold">
                                {{ number_format($item->stock) }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">Current Stock</div>
                            @if($item->stock <= $item->minimum_stock)
                                <div class="text-xs mt-1">⚠️ Stock Rendah</div>
                                @endif
                        </div>

                        <div class="bg-white border border-gray-300 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold">{{ number_format($item->minimum_stock) }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">Minimum Stock</div>
                        </div>

                        <div class="bg-white border border-gray-300 rounded-lg p-4 text-center">
                            <div class="text-3xl font-bold">${{ number_format($item->selling_price, 2) }}
                            </div>
                            <div class="text-sm text-gray-600 mt-1">Selling Price</div>
                        </div>
                    </div>

                    <!-- Stock Value -->
                    <div class="bg-primary/5 border border-primary/10 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-primary mb-2">Stock Value</h3>
                        <div class="text-3xl font-bold text-purple">
                            ${{ number_format($item->stock * $item->selling_price, 2) }}
                        </div>
                        <p class="text-sm text-primary mt-1">
                            {{ number_format($item->stock) }} units × ${{ number_format($item->selling_price, 2) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ route('incoming_items.create', ['item_id' => $item->id]) }}"
                    class="bg-primary text-white px-6 py-3 rounded-md font-medium text-center transition duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Incoming Stock
                </a>

                <a href="{{ route('outgoing_items.create', ['item_id' => $item->id]) }}"
                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-md font-medium text-center transition duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                    </svg>
                    Record Outgoing Stock
                </a>
            </div>

            <!-- Recent Transactions -->
            <div class="mt-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaksi Terbaru</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Incoming -->
                    <div class="bg-white border border-gray-300 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-primary/5 border-b border-gray-200">
                            <h4 class="font-medium text-primary">Barang Masuk Terbaru</h4>
                        </div>
                        <div class="p-4">
                            @if($recentIncoming->count() > 0)
                            <div class="space-y-3">
                                @foreach($recentIncoming as $incoming)
                                <div class="flex justify-between items-center text-sm">
                                    <div>
                                        <div class="font-medium text-gray-900">+{{ number_format($incoming->quantity) }}
                                        </div>
                                        <div class="text-gray-500">{{ $incoming->created_at->format('M d, Y') }}</div>
                                    </div>
                                    <a href="{{ route('incoming_items.show', $incoming) }}"
                                        class="text-primary">Lihat</a>
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <a href="{{ route('incoming_items.index', ['item_id' => $item->id]) }}"
                                    class="text-sm text-primary">Lihat Semua →</a>
                            </div>
                            @else
                            <p class="text-gray-500 text-sm">No recent incoming transactions</p>
                            @endif
                        </div>
                    </div>

                    <!-- Recent Outgoing -->
                    <div class="bg-white border border-gray-300 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-red-50 border-b border-gray-200">
                            <h4 class="font-medium text-red-800">Recent Outgoing</h4>
                        </div>
                        <div class="p-4">
                            @if($recentOutgoing->count() > 0)
                            <div class="space-y-3">
                                @foreach($recentOutgoing as $outgoing)
                                <div class="flex justify-between items-center text-sm">
                                    <div>
                                        <div class="font-medium text-gray-900">-{{ number_format($outgoing->quantity) }}
                                        </div>
                                        <div class="text-gray-500">{{ $outgoing->created_at->format('M d, Y') }}</div>
                                    </div>
                                    <a href="{{ route('outgoing_items.show', $outgoing) }}"
                                        class="text-red-600 hover:text-red-800">View</a>
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <a href="{{ route('outgoing_items.index', ['item_id' => $item->id]) }}"
                                    class="text-sm text-red-600 hover:text-red-800">View all outgoing transactions →</a>
                            </div>
                            @else
                            <p class="text-gray-500 text-sm">No recent outgoing transactions</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
