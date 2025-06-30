@extends('layouts.app')

@section('title', 'Record Outgoing Item')

@section('content')
<div class="mb-6">
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
            <li>
                <a href="{{ route('outgoing_items.index') }}" class="text-gray-400 hover:text-gray-500">Outgoing
                    Items</a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="ml-4 text-sm font-medium text-gray-500">Record</span>
                </div>
            </li>
        </ol>
    </nav>

    <h1 class="mt-4 text-3xl font-bold text-gray-900">Record Outgoing Item</h1>
    <p class="text-gray-600">Log items dispatched from inventory</p>
</div>

<div class="max-w-2xl">
    <form action="{{ route('outgoing_items.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <div class="md:col-span-1">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Outgoing Details</h3>
                    <p class="mt-1 text-sm text-gray-500">Information about the outgoing item.</p>
                </div>
                <div class="mt-5 md:mt-0 md:col-span-2">
                    <div class="space-y-6">
                        <div>
                            <label for="outgoing_date" class="block text-sm font-medium text-gray-700">Outgoing Date
                                *</label>
                            <input type="date" name="outgoing_date" id="outgoing_date"
                                value="{{ old('outgoing_date', date('Y-m-d')) }}" required
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            @error('outgoing_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="item_id" class="block text-sm font-medium text-gray-700">Item *</label>
                            <select name="item_id" id="item_id" required
                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Select an item</option>
                                @foreach($items as $item)
                                <option value="{{ $item->id }}" data-stock="{{ $item->stock }}" {{
                                    old('item_id')==$item->id ? 'selected' : '' }}>
                                    {{ $item->item_name }} ({{ $item->item_code }}) - Available: {{ $item->stock }}
                                </option>
                                @endforeach
                            </select>
                            @error('item_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity *</label>
                            <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}" min="1"
                                required
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            <p id="stock-warning" class="mt-1 text-sm text-red-600 hidden">Insufficient stock!</p>
                            @error('quantity')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="recipient" class="block text-sm font-medium text-gray-700">Recipient *</label>
                            <input type="text" name="recipient" id="recipient" value="{{ old('recipient') }}" required
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                placeholder="Enter recipient name">
                            @error('recipient')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3"
                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                placeholder="Optional notes about this outgoing shipment">{{ old('description') }}</textarea>
                            @error('description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        Stock will be updated automatically
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>When you record this outgoing item, the stock quantity will be automatically decreased for
                            the selected item. Make sure you have sufficient stock before proceeding.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('outgoing_items.index') }}"
                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancel
            </a>
            <button type="submit" id="submit-btn"
                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Record Outgoing
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const itemSelect = document.getElementById('item_id');
    const quantityInput = document.getElementById('quantity');
    const stockWarning = document.getElementById('stock-warning');
    const submitBtn = document.getElementById('submit-btn');

    function checkStock() {
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        const availableStock = selectedOption.getAttribute('data-stock') || 0;
        const requestedQuantity = parseInt(quantityInput.value) || 0;

        if (requestedQuantity > availableStock && availableStock > 0) {
            stockWarning.classList.remove('hidden');
            stockWarning.textContent = `Insufficient stock! Only ${availableStock} available.`;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            stockWarning.classList.add('hidden');
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    itemSelect.addEventListener('change', checkStock);
    quantityInput.addEventListener('input', checkStock);
});
</script>
@endsection
