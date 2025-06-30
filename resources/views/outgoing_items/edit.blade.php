@extends('layouts.app')

@section('title', 'Edit Outgoing Item')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Edit Outgoing Item</h1>
                <div class="flex space-x-2">
                    <a href="{{ route('outgoing_items.show', $outgoingItem) }}"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        View
                    </a>
                    <a href="{{ route('outgoing_items.index') }}"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">There were some errors with your submission</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <form action="{{ route('outgoing_items.update', $outgoingItem) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Item Selection -->
                    <div>
                        <label for="item_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Item <span class="text-red-500">*</span>
                        </label>
                        <select id="item_id" name="item_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('item_id') border-red-500 @enderror">
                            <option value="">Select an item</option>
                            @foreach($items as $item)
                            <option value="{{ $item->id }}" {{ old('item_id', $outgoingItem->item_id) == $item->id ?
                                'selected' : '' }}
                                data-stock="{{ $item->stock }}"
                                data-price="{{ $item->selling_price }}"
                                data-min-stock="{{ $item->minimum_stock }}">
                                {{ $item->name }} (SKU: {{ $item->sku }}) - Available: {{ $item->stock }}
                            </option>
                            @endforeach
                        </select>
                        @error('item_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                            Quantity <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="quantity" name="quantity" min="1"
                            value="{{ old('quantity', $outgoingItem->quantity) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('quantity') border-red-500 @enderror"
                            placeholder="Enter quantity">
                        <div id="stock-warning" class="hidden mt-1 text-sm text-red-600">
                            ⚠️ Insufficient stock available
                        </div>
                        <div id="low-stock-warning" class="hidden mt-1 text-sm text-yellow-600">
                            ⚠️ This will bring stock below minimum level
                        </div>
                        @error('quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Customer -->
                    <div>
                        <label for="customer" class="block text-sm font-medium text-gray-700 mb-2">
                            Customer
                        </label>
                        <input type="text" id="customer" name="customer"
                            value="{{ old('customer', $outgoingItem->customer) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('customer') border-red-500 @enderror"
                            placeholder="Enter customer name">
                        @error('customer')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Purpose -->
                    <div>
                        <label for="purpose" class="block text-sm font-medium text-gray-700 mb-2">
                            Purpose
                        </label>
                        <select id="purpose" name="purpose"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('purpose') border-red-500 @enderror">
                            <option value="">Select purpose</option>
                            <option value="Sale" {{ old('purpose', $outgoingItem->purpose) == 'Sale' ? 'selected' : ''
                                }}>Sale</option>
                            <option value="Internal Use" {{ old('purpose', $outgoingItem->purpose) == 'Internal Use' ?
                                'selected' : '' }}>Internal Use</option>
                            <option value="Damaged" {{ old('purpose', $outgoingItem->purpose) == 'Damaged' ? 'selected'
                                : '' }}>Damaged</option>
                            <option value="Return" {{ old('purpose', $outgoingItem->purpose) == 'Return' ? 'selected' :
                                '' }}>Return</option>
                            <option value="Transfer" {{ old('purpose', $outgoingItem->purpose) == 'Transfer' ?
                                'selected' : '' }}>Transfer</option>
                            <option value="Other" {{ old('purpose', $outgoingItem->purpose) == 'Other' ? 'selected' : ''
                                }}>Other</option>
                        </select>
                        @error('purpose')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unit Price -->
                    <div>
                        <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-2">
                            Unit Price
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" id="unit_price" name="unit_price" step="0.01" min="0"
                                value="{{ old('unit_price', $outgoingItem->unit_price) }}"
                                class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('unit_price') border-red-500 @enderror"
                                placeholder="0.00">
                        </div>
                        @error('unit_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Total Value (Display Only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Total Value
                        </label>
                        <div
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-900 font-semibold">
                            $<span id="total-value">0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Notes
                    </label>
                    <textarea id="notes" name="notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('notes') border-red-500 @enderror"
                        placeholder="Enter any additional notes...">{{ old('notes', $outgoingItem->notes) }}</textarea>
                    @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Warning Notice -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Important Notice</h3>
                            <p class="text-sm text-yellow-700 mt-1">
                                Editing this transaction will automatically adjust the item's stock level.
                                The difference between the old and new quantity will be applied to the current stock.
                                Please ensure sufficient stock is available.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('outgoing_items.show', $outgoingItem) }}"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition duration-200">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition duration-200">
                        Update Outgoing Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const itemSelect = document.getElementById('item_id');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unit_price');
    const totalValueSpan = document.getElementById('total-value');
    const stockWarning = document.getElementById('stock-warning');
    const lowStockWarning = document.getElementById('low-stock-warning');

    function updateCalculations() {
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        const quantity = parseInt(quantityInput.value) || 0;
        const unitPrice = parseFloat(unitPriceInput.value) || 0;

        // Update total value
        const totalValue = quantity * unitPrice;
        totalValueSpan.textContent = totalValue.toFixed(2);

        // Check stock availability
        if (selectedOption.value && quantity > 0) {
            const availableStock = parseInt(selectedOption.dataset.stock);
            const minStock = parseInt(selectedOption.dataset.minStock);
            const newStock = availableStock - quantity;

            // Show/hide warnings
            if (quantity > availableStock) {
                stockWarning.classList.remove('hidden');
                lowStockWarning.classList.add('hidden');
            } else if (newStock < minStock) {
                stockWarning.classList.add('hidden');
                lowStockWarning.classList.remove('hidden');
            } else {
                stockWarning.classList.add('hidden');
                lowStockWarning.classList.add('hidden');
            }
        } else {
            stockWarning.classList.add('hidden');
            lowStockWarning.classList.add('hidden');
        }
    }

    // Set initial unit price from item selection
    itemSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value && !unitPriceInput.value) {
            unitPriceInput.value = selectedOption.dataset.price;
        }
        updateCalculations();
    });

    quantityInput.addEventListener('input', updateCalculations);
    unitPriceInput.addEventListener('input', updateCalculations);

    // Initial calculation
    updateCalculations();
});
</script>
@endsection
