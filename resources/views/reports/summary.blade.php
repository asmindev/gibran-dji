@extends('layouts.app')

@section('title', 'Summary Report')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Summary Report</h1>
                <div class="flex space-x-2">
                    <button onclick="window.print()"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        Print Report
                    </button>
                    <a href="{{ route('reports.index') }}"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-200">
                        Back to Reports
                    </a>
                </div>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="p-6 bg-gray-50 border-b">
            <form method="GET" action="{{ route('reports.summary') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div class="flex items-end">
                    <button type="submit"
                        class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md font-medium transition duration-200">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Overall Summary Cards -->
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Overall Summary</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-lg shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-blue-100">Total Items</p>
                            <p class="text-3xl font-bold">{{ $summary['total_items'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-lg shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-green-100">Incoming Items</p>
                            <p class="text-3xl font-bold">{{ number_format($summary['total_incoming']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-red-500 to-red-600 text-white p-6 rounded-lg shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H3"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-red-100">Outgoing Items</p>
                            <p class="text-3xl font-bold">{{ number_format($summary['total_outgoing']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-6 rounded-lg shadow-lg">
                    <div class="flex items-center">
                        <div class="p-3 bg-white/20 rounded-lg">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1">
                                </path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-purple-100">Total Value</p>
                            <p class="text-3xl font-bold">${{ number_format($summary['total_value'], 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white border rounded-lg p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Incoming Transactions</span>
                            <span class="font-semibold text-green-600">{{ $summary['incoming_transactions'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Outgoing Transactions</span>
                            <span class="font-semibold text-red-600">{{ $summary['outgoing_transactions'] }}</span>
                        </div>
                        <div class="border-t pt-2">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-800">Total Transactions</span>
                                <span class="font-bold text-gray-900">{{ $summary['total_transactions'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white border rounded-lg p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Value Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Incoming Value</span>
                            <span class="font-semibold text-green-600">${{ number_format($summary['incoming_value'], 2)
                                }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Outgoing Value</span>
                            <span class="font-semibold text-red-600">${{ number_format($summary['outgoing_value'], 2)
                                }}</span>
                        </div>
                        <div class="border-t pt-2">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-800">Net Movement</span>
                                <span
                                    class="font-bold {{ $summary['net_movement'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ${{ number_format($summary['net_movement'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Category Breakdown</h3>
                <div class="bg-white border rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Category</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Items</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Current Stock</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Stock Value</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Low Stock Items</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($summary['category_breakdown'] as $category)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $category['name'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{
                                    $category['total_items'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ number_format($category['current_stock']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($category['stock_value'], 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($category['low_stock_items'] > 0)
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        {{ $category['low_stock_items'] }}
                                    </span>
                                    @else
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        0
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Items -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white border rounded-lg p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Most Incoming Items</h3>
                    <div class="space-y-3">
                        @foreach($summary['top_incoming_items'] as $item)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                @if($item->image)
                                <img class="h-8 w-8 rounded object-cover mr-3" src="{{ Storage::url($item->image) }}"
                                    alt="{{ $item->name }}">
                                @else
                                <div class="h-8 w-8 rounded bg-gray-200 flex items-center justify-center mr-3">
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $item->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $item->category->name }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-green-600">+{{ number_format($item->incoming_total)
                                }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white border rounded-lg p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Most Outgoing Items</h3>
                    <div class="space-y-3">
                        @foreach($summary['top_outgoing_items'] as $item)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                @if($item->image)
                                <img class="h-8 w-8 rounded object-cover mr-3" src="{{ Storage::url($item->image) }}"
                                    alt="{{ $item->name }}">
                                @else
                                <div class="h-8 w-8 rounded bg-gray-200 flex items-center justify-center mr-3">
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $item->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $item->category->name }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-red-600">-{{ number_format($item->outgoing_total)
                                }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }

        .max-w-7xl,
        .max-w-7xl * {
            visibility: visible;
        }

        .max-w-7xl {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .no-print {
            display: none !important;
        }
    }
</style>
@endsection
