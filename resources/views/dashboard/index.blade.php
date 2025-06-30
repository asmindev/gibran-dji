@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
    <p class="text-gray-600">Welcome to your inventory management system</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4-8-4m16 0v10l-8 4-8-4V7"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Items</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $totalItems }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Categories</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $totalCategories }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Low Stock Items</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $lowStockItems->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Stock</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $latestItems->sum('stock') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Low Stock Alert -->
    @if($lowStockItems->count() > 0)
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">⚠️ Low Stock Alert</h3>
            <div class="space-y-3">
                @foreach($lowStockItems->take(5) as $item)
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $item->item_name }}</p>
                        <p class="text-sm text-gray-500">{{ $item->item_code }} • {{ $item->category->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-red-600">{{ $item->stock }} left</p>
                        <a href="{{ route('items.show', $item) }}"
                            class="text-xs text-indigo-600 hover:text-indigo-500">View item</a>
                    </div>
                </div>
                @endforeach
                @if($lowStockItems->count() > 5)
                <div class="text-center">
                    <a href="{{ route('reports.stock') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                        View all {{ $lowStockItems->count() }} low stock items →
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Latest Items -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">📦 Latest Items</h3>
            <div class="space-y-3">
                @forelse($latestItems as $item)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $item->item_name }}</p>
                        <p class="text-sm text-gray-500">{{ $item->item_code }} • {{ $item->category->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">Stock: {{ $item->stock }}</p>
                        <p class="text-xs text-gray-500">{{ $item->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No items yet. <a href="{{ route('items.create') }}"
                        class="text-indigo-600 hover:text-indigo-500">Create your first item</a></p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Incoming Items -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">📥 Recent Incoming</h3>
            <div class="space-y-3">
                @forelse($recentIncoming as $incoming)
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $incoming->item->item_name }}</p>
                        <p class="text-sm text-gray-500">From: {{ $incoming->supplier }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-green-600">+{{ $incoming->quantity }}</p>
                        <p class="text-xs text-gray-500">{{ $incoming->incoming_date->format('M d, Y') }}</p>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No incoming items yet. <a
                        href="{{ route('incoming_items.create') }}" class="text-indigo-600 hover:text-indigo-500">Record
                        first incoming</a></p>
                @endforelse
            </div>
            @if($recentIncoming->count() > 0)
            <div class="mt-4 text-center">
                <a href="{{ route('incoming_items.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">View
                    all incoming items →</a>
            </div>
            @endif
        </div>
    </div>

    <!-- Recent Outgoing Items -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">📤 Recent Outgoing</h3>
            <div class="space-y-3">
                @forelse($recentOutgoing as $outgoing)
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $outgoing->item->item_name }}</p>
                        <p class="text-sm text-gray-500">To: {{ $outgoing->recipient }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-blue-600">-{{ $outgoing->quantity }}</p>
                        <p class="text-xs text-gray-500">{{ $outgoing->outgoing_date->format('M d, Y') }}</p>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No outgoing items yet. <a
                        href="{{ route('outgoing_items.create') }}" class="text-indigo-600 hover:text-indigo-500">Record
                        first outgoing</a></p>
                @endforelse
            </div>
            @if($recentOutgoing->count() > 0)
            <div class="mt-4 text-center">
                <a href="{{ route('outgoing_items.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">View
                    all outgoing items →</a>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-8">
    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('items.create') }}"
            class="group relative bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg shadow hover:shadow-md transition-shadow">
            <div>
                <span class="rounded-lg inline-flex p-3 bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </span>
            </div>
            <div class="mt-8">
                <h3 class="text-lg font-medium">
                    <span class="absolute inset-0" aria-hidden="true"></span>
                    Add Item
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    Create a new item in inventory
                </p>
            </div>
        </a>

        <a href="{{ route('incoming_items.create') }}"
            class="group relative bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg shadow hover:shadow-md transition-shadow">
            <div>
                <span class="rounded-lg inline-flex p-3 bg-green-50 text-green-600 group-hover:bg-green-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                    </svg>
                </span>
            </div>
            <div class="mt-8">
                <h3 class="text-lg font-medium">
                    <span class="absolute inset-0" aria-hidden="true"></span>
                    Record Incoming
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    Log items received
                </p>
            </div>
        </a>

        <a href="{{ route('outgoing_items.create') }}"
            class="group relative bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg shadow hover:shadow-md transition-shadow">
            <div>
                <span class="rounded-lg inline-flex p-3 bg-blue-50 text-blue-600 group-hover:bg-blue-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </span>
            </div>
            <div class="mt-8">
                <h3 class="text-lg font-medium">
                    <span class="absolute inset-0" aria-hidden="true"></span>
                    Record Outgoing
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    Log items dispatched
                </p>
            </div>
        </a>

        <a href="{{ route('reports.stock') }}"
            class="group relative bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-indigo-500 rounded-lg shadow hover:shadow-md transition-shadow">
            <div>
                <span class="rounded-lg inline-flex p-3 bg-yellow-50 text-yellow-600 group-hover:bg-yellow-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </span>
            </div>
            <div class="mt-8">
                <h3 class="text-lg font-medium">
                    <span class="absolute inset-0" aria-hidden="true"></span>
                    View Reports
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    Check stock and transactions
                </p>
            </div>
        </a>
    </div>
</div>
@endsection
