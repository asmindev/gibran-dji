@extends('layouts.app')

@section('title', 'Outgoing Item Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-red-500 to-red-600 text-white">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Outgoing Item Details</h1>
                <div class="flex space-x-2">
                    <a href="{{ route('outgoing_items.edit', $outgoingItem) }}"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        Edit
                    </a>
                    <a href="{{ route('outgoing_items.index') }}"
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
                            <p class="text-gray-900 font-mono">#{{ str_pad($outgoingItem->id, 6, '0', STR_PAD_LEFT) }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Date</label>
                            <p class="text-gray-900">{{ $outgoingItem->created_at->format('M d, Y') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Time</label>
                            <p class="text-gray-900">{{ $outgoingItem->created_at->format('H:i:s') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-600">Quantity</label>
                            <p class="font-semibold text-red-600">-{{ number_format($outgoingItem->quantity) }}</p>
                        </div>
                    </div>

                    @if($outgoingItem->customer)
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Customer</label>
                        <p class="text-gray-900">{{ $outgoingItem->customer }}</p>
                    </div>
                    @endif

                    @if($outgoingItem->purpose)
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Purpose</label>
                        <p class="text-gray-900">{{ $outgoingItem->purpose }}</p>
                    </div>
                    @endif

                    @if($outgoingItem->notes)
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Notes</label>
                        <p class="text-gray-900 bg-gray-50 p-3 rounded-md">{{ $outgoingItem->notes }}</p>
                    </div>
                    @endif
                </div>

                <!-- Item Details -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Item Details</h3>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        @if($outgoingItem->item->image)
                        <div class="mb-4">
                            <img src="{{ Storage::url($outgoingItem->item->image) }}"
                                alt="{{ $outgoingItem->item->name }}" class="w-32 h-32 object-cover rounded-lg mx-auto">
                        </div>
                        @endif

                        <div class="text-center">
                            <h4 class="font-semibold text-lg text-gray-900">{{ $outgoingItem->item->name }}</h4>
                            <p class="text-gray-600">{{ $outgoingItem->item->category->name }}</p>
                            <p class="text-sm text-gray-500 mt-2">SKU: {{ $outgoingItem->item->sku }}</p>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div class="text-center">
                                <p class="text-gray-600">Current Stock</p>
                                <p
                                    class="font-semibold text-2xl {{ $outgoingItem->item->stock <= $outgoingItem->item->minimum_stock ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($outgoingItem->item->stock) }}
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="text-gray-600">Unit Price</p>
                                <p class="font-semibold text-2xl text-gray-900">
                                    ${{ number_format($outgoingItem->item->selling_price, 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Impact -->
            <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Stock Updated</h3>
                        <p class="text-sm text-red-700">
                            This transaction removed <strong>{{ number_format($outgoingItem->quantity) }}</strong> units
                            from the inventory.
                            Current stock level: <strong>{{ number_format($outgoingItem->item->stock) }}</strong> units.
                            @if($outgoingItem->item->stock <= $outgoingItem->item->minimum_stock)
                                <span class="font-semibold text-red-600">⚠️ Stock is below minimum level!</span>
                                @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Transaction Value -->
            @if($outgoingItem->unit_price)
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-blue-800">Transaction Value</h3>
                        <p class="text-sm text-blue-700">
                            Unit Price: ${{ number_format($outgoingItem->unit_price, 2) }} × {{
                            number_format($outgoingItem->quantity) }} units
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-blue-900">
                            ${{ number_format($outgoingItem->unit_price * $outgoingItem->quantity, 2) }}
                        </p>
                        <p class="text-sm text-blue-600">Total Value</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
