@extends('layouts.app')

@section('title', 'Edit Incoming Item')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Edit Incoming Item</h1>
                <div class="flex space-x-2">
                    <a href="{{ route('incoming_items.show', $incomingItem) }}"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        View
                    </a>
                    <a href="{{ route('incoming_items.index') }}"
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

            <form action="{{ route('incoming_items.update', $incomingItem) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Item Selection -->
                    <div>
                        <label for="item_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Item <span class="text-red-500">*</span>
                        </label>
                        <select id="item_id" name="item_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('item_id') border-red-500 @enderror">
                            <option value="">Select an item</option>
                            @foreach($items as $item)
                            <option value="{{ $item->id }}" {{ old('item_id', $incomingItem->item_id) == $item->id ?
                                'selected' : '' }}
                                data-stock="{{ $item->stock }}"
                                data-price="{{ $item->selling_price }}">
                                {{ $item->name }} (SKU: {{ $item->sku }}) - Current Stock: {{ $item->stock }}
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
                            value="{{ old('quantity', $incomingItem->quantity) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('quantity') border-red-500 @enderror"
                            placeholder="Enter quantity">
                        @error('quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Supplier -->
                    <div>
                        <label for="supplier" class="block text-sm font-medium text-gray-700 mb-2">
                            Supplier
                        </label>
                        <input type="text" id="supplier" name="supplier"
                            value="{{ old('supplier', $incomingItem->supplier) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('supplier') border-red-500 @enderror"
                            placeholder="Enter supplier name">
                        @error('supplier')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unit Cost -->
                    <div>
                        <label for="unit_cost" class="block text-sm font-medium text-gray-700 mb-2">
                            Unit Cost
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0"
                                value="{{ old('unit_cost', $incomingItem->unit_cost) }}"
                                class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('unit_cost') border-red-500 @enderror"
                                placeholder="0.00">
                        </div>
                        @error('unit_cost')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Notes
                    </label>
                    <textarea id="notes" name="notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('notes') border-red-500 @enderror"
                        placeholder="Enter any additional notes...">{{ old('notes', $incomingItem->notes) }}</textarea>
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
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('incoming_items.show', $incomingItem) }}"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-200">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-200">
                        Update Incoming Item
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

    // Update total cost when quantity or item changes
    function updateCalculations() {
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        if (selectedOption.value && quantityInput.value) {
            const stock = selectedOption.dataset.stock;
            const price = selectedOption.dataset.price;
            // You can add more calculations here if needed
        }
    }

    itemSelect.addEventListener('change', updateCalculations);
    quantityInput.addEventListener('input', updateCalculations);
});
</script>
@endsection
