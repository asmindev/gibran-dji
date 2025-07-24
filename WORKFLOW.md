# 🔄 WORKFLOW SISTEM ANALISIS INVENTORY DENGAN MACHINE LEARNING

## 📋 OVERVIEW SISTEM

Sistem Analisis Inventory ini adalah aplikasi berbasis Laravel yang mengintegrasikan Machine Learning untuk memberikan insight bisnis melalui:

-   **Association Rule Mining** (Algoritma Apriori) - Menemukan pola kombinasi produk
-   **Demand Forecasting** (Random Forest) - Prediksi permintaan masa depan

## 🏗️ ARSITEKTUR SISTEM

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │    Backend      │    │  Machine        │
│   (Blade/JS)    │◄──►│   (Laravel)     │◄──►│  Learning       │
│                 │    │                 │    │  (Python)       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
        │                        │                        │
        ▼                        ▼                        ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Dashboard     │    │   MySQL         │    │   CSV Files     │
│   Analysis      │    │   Database      │    │   + Results     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 🎯 DATA FLOW & WORKFLOW

### 1. MASTER DATA MANAGEMENT

#### 1.1 Setup Kategori & Items

```
Admin → Kategori → Items → Set Stock Levels
```

**Proses:**

1. Admin membuat kategori produk (Olahraga Futsal, Football, Badminton, dll)
2. Input items per kategori dengan spesifikasi lengkap
3. Set minimum stock level untuk monitoring
4. Tentukan harga beli dan jual

**File Terlibat:**

-   `app/Http/Controllers/CategoryController.php`
-   `app/Http/Controllers/ItemController.php`
-   `app/Models/Category.php`
-   `app/Models/Item.php`

### 2. OPERATIONAL WORKFLOW

#### 2.1 Daily Operations

```
Supplier → Incoming Items → Stock Update → Sales → Outgoing Items → Stock Decrease
```

**A. Proses Barang Masuk:**

1. **Input Incoming Items**
    - Supplier delivery barang
    - Admin input data via form atau import CSV
    - System update stock otomatis
    - Generate laporan penerimaan

**B. Proses Barang Keluar:**

1. **Sales Transaction**
    - Customer order/pembelian
    - Input outgoing items
    - System kurangi stock otomatis
    - Record transaction details (customer, recipient, notes)

**File Terlibat:**

-   `app/Http/Controllers/IncomingItemController.php`
-   `app/Http/Controllers/OutgoingItemController.php`
-   `app/Models/IncomingItem.php`
-   `app/Models/OutgoingItem.php`

### 3. MACHINE LEARNING ANALYSIS WORKFLOW

#### 3.1 Automated Analysis Pipeline

```
Transaction Data → Export CSV → Python ML → Import Results → Dashboard
```

**Step-by-Step Process:**

##### **STEP 1: Data Export**

```php
// InventoryAnalysisService::exportTransactionData()
SELECT
    items.item_code,
    items.item_name,
    outgoing_items.outgoing_date,
    outgoing_items.quantity,
    outgoing_items.customer
FROM outgoing_items
JOIN items ON outgoing_items.item_id = items.id
WHERE outgoing_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
```

**Output:** `storage/app/inventory_analysis/transactions.csv`

##### **STEP 2: Python ML Processing**

```python
# scripts/analyze_inventory.py
class InventoryAnalyzer:
    def run_analysis():
        1. load_data()           # Load CSV data
        2. perform_apriori()     # Association rules
        3. perform_prediction()  # Demand forecasting
        4. save_results()        # Export results
```

**A. Apriori Association Analysis:**

-   **Input:** Transaction baskets grouped by date/customer
-   **Process:**
    -   Find frequent itemsets (min_support = 0.01)
    -   Generate association rules (min_confidence = 0.5)
    -   Calculate support, confidence, lift metrics
-   **Output:** Association rules dengan ranking

**B. Random Forest Demand Prediction:**

-   **Input:** Time series data per item
-   **Features:**
    -   day_of_week, month, day_of_month
    -   rolling_7_avg, rolling_30_avg
    -   lag_1, lag_7 (previous values)
-   **Process:** Train RF model per item
-   **Output:** 30-day demand forecast dengan confidence

##### **STEP 3: Results Import**

```php
// InventoryAnalysisService::importAnalysisResults()
1. Import recommendations.csv → inventory_recommendations table
2. Import predictions.csv → stock_predictions table
3. Update analysis metadata
```

#### 3.2 Manual vs Automated Execution

**Manual Trigger:**

```bash
# Via Command Line
php artisan inventory:analyze

# Via Web Interface
POST /analysis/run → AnalysisController@runAnalysis
```

**Automated Schedule:**

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('inventory:analyze')
             ->weekly()
             ->sundays()
             ->at('02:00');
}
```

### 4. DASHBOARD & REPORTING WORKFLOW

#### 4.1 Analysis Dashboard Flow

```
User → /analysis → View Metrics → Trigger Analysis → View Results
```

**Dashboard Components:**

**A. Overview Cards:**

-   Total Active Recommendations
-   Total Active Predictions
-   High Confidence Recommendations
-   High Confidence Predictions

**B. Action Buttons:**

-   **Run Analysis**: Trigger new ML analysis
-   **View Recommendations**: Browse association rules
-   **View Predictions**: Check demand forecasts

**C. Results Tables:**

-   **Top Recommendations**: Item combinations dengan lift tinggi
-   **Demand Predictions**: Forecast per item dengan confidence

#### 4.2 Reports Generation

```
Raw Data → Processing → Charts/Tables → Export Options
```

**Available Reports:**

-   Stock Report (current levels vs minimum)
-   Incoming Items Report (by date range)
-   Outgoing Items Report (sales analysis)
-   Summary Report (overview metrics)
-   **NEW:** Analysis Report (ML insights)

## 🔧 TECHNICAL IMPLEMENTATION

### 5. FILE STRUCTURE & RESPONSIBILITIES

```
├── app/
│   ├── Console/Commands/
│   │   └── InventoryAnalyzeCommand.php      # ML analysis command
│   ├── Http/Controllers/
│   │   ├── AnalysisController.php           # Analysis dashboard
│   │   └── OutgoingItemController.php       # Extended with analysis
│   ├── Models/
│   │   ├── InventoryRecommendation.php      # Association rules storage
│   │   └── StockPrediction.php              # Demand predictions storage
│   └── Services/
│       └── InventoryAnalysisService.php     # Core ML orchestration
├── database/
│   ├── migrations/
│   │   ├── *_create_inventory_recommendations_table.php
│   │   └── *_create_stock_predictions_table.php
│   └── seeders/
│       └── InventoryAnalysisSampleSeeder.php # Sample data generation
├── resources/views/
│   └── analysis/
│       └── index.blade.php                  # Analysis dashboard
├── routes/
│   └── web.php                              # Analysis routes
└── scripts/
    └── analyze_inventory.py                 # Python ML script
```

### 6. DATABASE SCHEMA

#### 6.1 Core Tables

```sql
-- Existing tables
categories (id, name, description)
items (id, item_code, item_name, category_id, stock, minimum_stock, purchase_price, selling_price)
incoming_items (id, item_id, quantity, unit_cost, supplier, incoming_date, notes)
outgoing_items (id, item_id, quantity, unit_price, customer, recipient, outgoing_date, notes)

-- ML Results tables
inventory_recommendations (id, antecedents, consequents, support, confidence, lift, is_active, created_at)
stock_predictions (id, item_code, item_name, predicted_demand, confidence, period_start, period_end, created_at)
```

#### 6.2 Data Relationships

```
Categories (1:N) Items (1:N) IncomingItems
                     (1:N) OutgoingItems → ML Analysis → Recommendations + Predictions
```

### 7. API ENDPOINTS

#### 7.1 Analysis Endpoints

```php
// Analysis Dashboard
GET  /analysis                    # Dashboard view
POST /analysis/run               # Trigger analysis
GET  /analysis/recommendations   # View recommendations
GET  /analysis/predictions       # View predictions

// Data Management
GET  /outgoing-items/export      # Export transactions CSV
POST /outgoing-items/import      # Import transactions CSV
```

### 8. CONFIGURATION & ENVIRONMENT

#### 8.1 Required Setup

```bash
# PHP Dependencies
composer install

# Python Environment
python -m venv .venv
source .venv/bin/activate  # Linux/Mac
pip install pandas numpy scikit-learn mlxtend

# Database
php artisan migrate

# Sample Data (Optional)
php artisan db:seed --class=InventoryAnalysisSampleSeeder
```

#### 8.2 Environment Variables

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=inventory_db
DB_USERNAME=root
DB_PASSWORD=password

# Python Path (if needed)
PYTHON_PATH=/usr/bin/python3
```

## 🚀 DEPLOYMENT WORKFLOW

### 9. DEVELOPMENT TO PRODUCTION

#### 9.1 Development

```bash
1. Clone repository
2. Install dependencies (PHP + Python)
3. Setup database
4. Generate sample data
5. Test ML analysis
6. Develop features
```

#### 9.2 Testing

```bash
1. Unit tests (PHPUnit)
2. Integration tests
3. ML accuracy tests
4. Performance tests
5. UI/UX testing
```

#### 9.3 Production Deployment

```bash
1. Server setup (PHP, Python, MySQL)
2. Environment configuration
3. Database migration
4. Cron job setup for automated analysis
5. Monitoring & logging setup
6. Performance optimization
```

### 10. MONITORING & MAINTENANCE

#### 10.1 System Monitoring

-   **Performance**: Analysis execution time
-   **Accuracy**: ML model performance metrics
-   **Storage**: CSV files and database growth
-   **Errors**: Failed analysis attempts

#### 10.2 Data Quality

-   **Completeness**: Missing transaction data
-   **Consistency**: Data validation rules
-   **Timeliness**: Real-time vs batch processing
-   **Accuracy**: Business logic validation

## 📊 BUSINESS VALUE WORKFLOW

### 11. DECISION MAKING PROCESS

```
Raw Data → ML Insights → Business Intelligence → Strategic Decisions → Improved Performance
```

#### 11.1 Association Rules Usage

1. **Cross-selling**: Recommend complementary products
2. **Inventory Planning**: Stock related items together
3. **Marketing**: Bundle products for promotions
4. **Store Layout**: Place associated items nearby

#### 11.2 Demand Prediction Usage

1. **Stock Planning**: Avoid stockouts and overstock
2. **Procurement**: Optimize purchase timing
3. **Cash Flow**: Better financial planning
4. **Customer Service**: Improve availability

### 12. SUCCESS METRICS

#### 12.1 Technical Metrics

-   Analysis completion rate: >95%
-   Prediction accuracy: >80%
-   System uptime: >99%
-   Response time: <30s for analysis

#### 12.2 Business Metrics

-   Stock turnover improvement: >15%
-   Stockout reduction: >20%
-   Cross-selling increase: >10%
-   Inventory costs reduction: >8%

---

## 🎯 QUICK START GUIDE

### For Developers:

```bash
git clone [repository]
cd inventory-analysis
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=InventoryAnalysisSampleSeeder
php artisan inventory:analyze
php artisan serve
```

### For Users:

1. Navigate to `/analysis`
2. Click "Run Analysis"
3. Wait for processing completion
4. Review recommendations and predictions
5. Export reports as needed

---

_Last Updated: July 2, 2025_
_Version: 1.0.0_
