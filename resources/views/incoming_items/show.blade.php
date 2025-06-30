@extends('layouts.app')

@section('title', 'Incoming Item Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Incoming Item Details</h1>
                <div class="flex space-x-2">
                    <a href="{{ route('incoming_items.edit', $incomingItem) }}"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        Edit
                    </a>
                    <a href="{{ route('incoming_items.index') }}"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Transaction Details -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Transaction Details</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Transaction ID</label>
                            <p class="text-gray-900 font-mono">#{{ str_pad($incomingItem->id, 6, '0', STR_PAD_LEFT) }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Date</label>
                            <p class="text-gray-900">{{ $incomingItem->created_at->format('M d, Y') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Time</label>
                            <p class="text-gray-900">{{ $incomingItem->created_at->format('H:i:s') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Quantity</label>
                            <p class="font-semibold text-green-600">+{{ number_format($incomingItem->quantity) }}</p>
                        </div>
                    </div>

                    @if($incomingItem->supplier)
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Supplier</label>
                        <p class="text-gray-900">{{ $incomingItem->supplier }}</p>
                    </div>
                    @endif

                    @if($incomingItem->notes)
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Notes</label>
                        <p class="text-gray-900 bg-gray-50 p-3 rounded-md">{{ $incomingItem->notes }}</p>
                    </div>
                    @endif
                </div>

                <!-- Item Details -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Item Details</h3>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        @if($incomingItem->item->image)
                        <div class="mb-4">
                            <img src="{{ Storage::url($incomingItem->item->image) }}"
                                alt="{{ $incomingItem->item->name }}" class="w-32 h-32 object-cover rounded-lg mx-auto">
                        </div>
                        @endif

                        <div class="text-center">
                            <h4 class="font-semibold text-lg text-gray-900">{{ $incomingItem->item->name }}</h4>
                            <p class="text-gray-600">{{ $incomingItem->item->category->name }}</p>
                            <p class="text-sm text-gray-500 mt-2">SKU: {{ $incomingItem->item->sku }}</p>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div class="text-center">
                                <p class="text-gray-600">Current Stock</p>
                                <p
                                    class="font-semibold text-2xl {{ $incomingItem->item->stock <= $incomingItem->item->minimum_stock ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($incomingItem->item->stock) }}
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="text-gray-600">Unit Price</p>
                                <p class="font-semibold text-2xl text-gray-900">
                                    ${{ number_format($incomingItem->item->selling_price, 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Impact -->
            <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Stock Updated</h3>
                        <p class="text-sm text-green-700">
                            This transaction added <strong>{{ number_format($incomingItem->quantity) }}</strong> units
                            to the inventory.
                            Current stock level: <strong>{{ number_format($incomingItem->item->stock) }}</strong> units.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
