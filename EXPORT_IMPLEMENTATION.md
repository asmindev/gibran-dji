# Export Implementation Guide

## 1. Install Required Packages

First, install the Laravel Excel package for export functionality:

```bash
composer require maatwebsite/excel
```

## 2. Create Export Classes

Create export classes for both incoming and outgoing items:

```bash
php artisan make:export IncomingItemsExport
php artisan make:export OutgoingItemsExport
```

## 3. IncomingItemsExport Class

Create `app/Exports/IncomingItemsExport.php`:

```php
<?php

namespace App\Exports;

use App\Models\IncomingItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Http\Request;

class IncomingItemsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = IncomingItem::with(['item.category']);

        // Apply filters
        if ($this->request->search) {
            $query->whereHas('item', function($q) {
                $q->where('item_name', 'like', '%' . $this->request->search . '%');
            });
        }

        if ($this->request->item_id) {
            $query->where('item_id', $this->request->item_id);
        }

        if ($this->request->start_date) {
            $query->whereDate('incoming_date', '>=', $this->request->start_date);
        }

        if ($this->request->end_date) {
            $query->whereDate('incoming_date', '<=', $this->request->end_date);
        }

        return $query->orderBy('incoming_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Masuk',
            'Kode Item',
            'Nama Item',
            'Kategori',
            'Jumlah',
            'Supplier',
            'Keterangan',
            'Tanggal Input'
        ];
    }

    public function map($incomingItem): array
    {
        static $no = 1;

        return [
            $no++,
            $incomingItem->incoming_date->format('d/m/Y'),
            $incomingItem->item->item_code,
            $incomingItem->item->item_name,
            $incomingItem->item->category->category_name,
            $incomingItem->quantity,
            $incomingItem->supplier,
            $incomingItem->description,
            $incomingItem->created_at->format('d/m/Y H:i')
        ];
    }
}
```

## 4. OutgoingItemsExport Class

Create `app/Exports/OutgoingItemsExport.php`:

```php
<?php

namespace App\Exports;

use App\Models\OutgoingItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Http\Request;

class OutgoingItemsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = OutgoingItem::with(['item.category']);

        // Apply filters
        if ($this->request->search) {
            $query->whereHas('item', function($q) {
                $q->where('item_name', 'like', '%' . $this->request->search . '%');
            });
        }

        if ($this->request->item_id) {
            $query->where('item_id', $this->request->item_id);
        }

        if ($this->request->start_date) {
            $query->whereDate('outgoing_date', '>=', $this->request->start_date);
        }

        if ($this->request->end_date) {
            $query->whereDate('outgoing_date', '<=', $this->request->end_date);
        }

        return $query->orderBy('outgoing_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Keluar',
            'Kode Item',
            'Nama Item',
            'Kategori',
            'Jumlah',
            'Penerima',
            'Tujuan',
            'Keterangan',
            'Tanggal Input'
        ];
    }

    public function map($outgoingItem): array
    {
        static $no = 1;

        return [
            $no++,
            $outgoingItem->outgoing_date->format('d/m/Y'),
            $outgoingItem->item->item_code,
            $outgoingItem->item->item_name,
            $outgoingItem->item->category->category_name,
            $outgoingItem->quantity,
            $outgoingItem->customer ?? $outgoingItem->recipient,
            $outgoingItem->purpose,
            $outgoingItem->notes ?? $outgoingItem->description,
            $outgoingItem->created_at->format('d/m/Y H:i')
        ];
    }
}
```

## 5. Add Export Methods to Controllers

### IncomingItemsController

Add this method to your `IncomingItemsController`:

```php
use App\Exports\IncomingItemsExport;
use Maatwebsite\Excel\Facades\Excel;

public function export(Request $request)
{
    $format = $request->get('format', 'excel');
    $filename = 'barang-masuk-' . date('Y-m-d-H-i-s');

    if ($format === 'csv') {
        return Excel::download(
            new IncomingItemsExport($request),
            $filename . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    return Excel::download(
        new IncomingItemsExport($request),
        $filename . '.xlsx'
    );
}
```

### OutgoingItemsController

Add this method to your `OutgoingItemsController`:

```php
use App\Exports\OutgoingItemsExport;
use Maatwebsite\Excel\Facades\Excel;

public function export(Request $request)
{
    $format = $request->get('format', 'excel');
    $filename = 'barang-keluar-' . date('Y-m-d-H-i-s');

    if ($format === 'csv') {
        return Excel::download(
            new OutgoingItemsExport($request),
            $filename . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    return Excel::download(
        new OutgoingItemsExport($request),
        $filename . '.xlsx'
    );
}
```

## 6. Add Routes

Add these routes to your `web.php`:

```php
// Export routes
Route::get('/incoming-items/export', [IncomingItemsController::class, 'export'])->name('incoming_items.export');
Route::get('/outgoing-items/export', [OutgoingItemsController::class, 'export'])->name('outgoing_items.export');
```

## 7. Update Controller Index Methods

Make sure your index methods include the search functionality:

### IncomingItemsController::index

```php
public function index(Request $request)
{
    $query = IncomingItem::with(['item.category']);

    // Search by item name
    if ($request->search) {
        $query->whereHas('item', function($q) use ($request) {
            $q->where('item_name', 'like', '%' . $request->search . '%');
        });
    }

    // Existing filters...
    if ($request->item_id) {
        $query->where('item_id', $request->item_id);
    }

    if ($request->start_date) {
        $query->whereDate('incoming_date', '>=', $request->start_date);
    }

    if ($request->end_date) {
        $query->whereDate('incoming_date', '<=', $request->end_date);
    }

    $incomingItems = $query->orderBy('incoming_date', 'desc')->paginate(15);
    $items = Item::orderBy('item_name')->get();

    return view('incoming_items.index', compact('incomingItems', 'items'));
}
```

### OutgoingItemsController::index

```php
public function index(Request $request)
{
    $query = OutgoingItem::with(['item.category']);

    // Search by item name
    if ($request->search) {
        $query->whereHas('item', function($q) use ($request) {
            $q->where('item_name', 'like', '%' . $request->search . '%');
        });
    }

    // Existing filters...
    if ($request->item_id) {
        $query->where('item_id', $request->item_id);
    }

    if ($request->start_date) {
        $query->whereDate('outgoing_date', '>=', $request->start_date);
    }

    if ($request->end_date) {
        $query->whereDate('outgoing_date', '<=', $request->end_date);
    }

    $outgoingItems = $query->orderBy('outgoing_date', 'desc')->paginate(15);
    $items = Item::orderBy('item_name')->get();

    return view('outgoing_items.index', compact('outgoingItems', 'items'));
}
```

## 8. Usage

After implementing the above code:

1. Users can search for items by name using the search input
2. Users can filter by item dropdown, start date, and end date
3. Users can export filtered data to Excel or CSV format
4. The export respects all active filters (search, item selection, date range)
5. The exported files have meaningful names with timestamps

## Features

-   ✅ Search by item name
-   ✅ Filter by specific item
-   ✅ Filter by date range
-   ✅ Export to Excel (.xlsx)
-   ✅ Export to CSV (.csv)
-   ✅ Export respects all active filters
-   ✅ Responsive dropdown design
-   ✅ Indonesian headers and formatting
-   ✅ Auto-numbered rows in export
