# 📊 Stock Prediction System - Parameter Guide

> Dokumentasi lengkap parameter sistem prediksi penjualan dan persediaan menggunakan Random Forest

## 📋 Daftar Isi

-   [Overview Sistem](#overview-sistem)
-   [Parameter Random Forest](#parameter-random-forest)
-   [Parameter Feature Engineering](#parameter-feature-engineering)
-   [Parameter Input Prediksi](#parameter-input-prediksi)
-   [Contoh Praktis](#contoh-praktis)
-   [Tips Optimasi](#tips-optimasi)

---

## 🏗️ Overview Sistem

### Arsitektur

```
Laravel Backend ←→ Python ML Engine
     ↓                    ↓
Database (MariaDB)    Random Forest Model
     ↓                    ↓
OutgoingItems      Feature Engineering
IncomingItems      ↓
StockPredictions   Prediction Results
```

### Flow Process

1. **Data Collection**: Laravel mengumpulkan data transaksi
2. **Export to CSV**: Data diekspor ke format CSV
3. **Feature Engineering**: Python membuat fitur dari data historis
4. **Model Training**: Random Forest dilatih dengan fitur tersebut
5. **Prediction**: Model memprediksi berdasarkan input parameter
6. **Result Storage**: Hasil disimpan kembali ke database Laravel

---

## 🌳 Parameter Random Forest

Parameter yang mengontrol algoritma Random Forest dalam file `utils.py`:

### Core Algorithm Parameters

| Parameter      | Nilai | Deskripsi                          | Impact                         |
| -------------- | ----- | ---------------------------------- | ------------------------------ |
| `N_ESTIMATORS` | 100   | Jumlah decision trees dalam forest | ↑ Akurasi, ↑ Training time     |
| `MAX_DEPTH`    | 8     | Kedalaman maksimal setiap tree     | ↑ Complexity, risk overfitting |
| `RANDOM_STATE` | 42    | Seed untuk reproducibility         | Consistent results             |
| `MIN_SAMPLES`  | 3     | Minimum data per produk            | Quality control                |
| `CV_SPLITS`    | 3     | Cross-validation folds             | Model validation               |

### Detail Penjelasan

#### 🌲 `N_ESTIMATORS = 100`

```python
RandomForestRegressor(n_estimators=100)
```

-   **Analogi**: Seperti survei dengan 100 responden vs 10 responden
-   **Fungsi**: Setiap tree memberikan vote, hasil final adalah rata-rata
-   **Trade-off**:
    -   ✅ Lebih banyak = lebih akurat
    -   ❌ Lebih banyak = training lebih lama
-   **Rekomendasi**:
    -   Development: 100
    -   Production: 200-300

#### 🏗️ `MAX_DEPTH = 8`

```python
RandomForestRegressor(max_depth=8)
```

-   **Analogi**: Maksimal 8 tingkat pertanyaan "Ya/Tidak"
-   **Fungsi**: Mengontrol kompleksitas model
-   **Trade-off**:
    -   ✅ Deeper = capture complex patterns
    -   ❌ Too deep = overfitting (hafal training data)
-   **Rekomendasi**:
    -   Small dataset: 6-8
    -   Large dataset: 10-12

#### 🎲 `RANDOM_STATE = 42`

```python
RandomForestRegressor(random_state=42)
```

-   **Fungsi**: Memastikan hasil sama setiap training
-   **Kegunaan**:
    -   Debugging konsisten
    -   A/B testing reliable
    -   Reproducible research

#### 📊 `MIN_SAMPLES = 3`

```python
product_counts[product_counts >= MIN_SAMPLES]
```

-   **Fungsi**: Filter produk dengan data minimal
-   **Alasan**: Prediksi tidak reliable dengan data terlalu sedikit
-   **Contoh**: Produk dengan hanya 1-2 transaksi tidak ditraining

---

## 📈 Parameter Feature Engineering

Fitur yang dibuat otomatis dari data historis penjualan:

### Time-based Features

| Feature         | Formula                       | Contoh   | Window  |
| --------------- | ----------------------------- | -------- | ------- |
| `prev_sales_1`  | `qty_sold.shift(1)`           | 5 unit   | 1 hari  |
| `prev_sales_7`  | `qty_sold.shift(7)`           | 3 unit   | 7 hari  |
| `prev_sales_30` | `qty_sold.shift(30)`          | 2 unit   | 30 hari |
| `avg_sales_7`   | `qty_sold.rolling(7).mean()`  | 4.2 unit | 7 hari  |
| `avg_sales_30`  | `qty_sold.rolling(30).mean()` | 3.8 unit | 30 hari |

### Detail Implementasi

#### 📅 Lag Features (Previous Sales)

```python
# Penjualan hari sebelumnya
sales_agg["prev_sales_1"] = sales_agg.groupby("id_item")["qty_sold"].shift(1)

# Penjualan minggu lalu (same day)
sales_agg["prev_sales_7"] = sales_agg.groupby("id_item")["qty_sold"].shift(7)
```

**Kegunaan**:

-   `prev_sales_1`: Menangkap momentum jangka pendek
-   `prev_sales_7`: Menangkap pola mingguan (Senin vs Sabtu)
-   `prev_sales_30`: Menangkap pola bulanan

#### 📊 Moving Averages

```python
# Rata-rata 7 hari terakhir
sales_agg["avg_sales_7"] = (
    sales_agg.groupby("id_item")["qty_sold"]
    .rolling(7, min_periods=1)
    .mean()
)
```

**Kegunaan**:

-   Menghaluskan fluktuasi harian
-   Menangkap trend general
-   Baseline performa produk

### Data Flow Example

```
Raw Data:
Tanggal     | id_item | qty_sold
------------|---------|----------
01-Jun-2025 | 1       | 2
02-Jun-2025 | 1       | 1
03-Jun-2025 | 1       | 3
04-Jun-2025 | 1       | 0
05-Jun-2025 | 1       | 2

Generated Features:
Tanggal     | qty_sold | prev_sales_1 | avg_sales_7
------------|----------|--------------|-------------
01-Jun-2025 | 2        | null         | 2.0
02-Jun-2025 | 1        | 2            | 1.5
03-Jun-2025 | 3        | 1            | 2.0
04-Jun-2025 | 0        | 3            | 1.5
05-Jun-2025 | 2        | 0            | 1.6
```

---

## 🔄 Parameter Input Prediksi

Parameter yang dikirim dari Laravel ke Python saat melakukan prediksi:

### Sales Prediction Parameters

| Parameter             | Type  | Default | Deskripsi                                       | Contoh |
| --------------------- | ----- | ------- | ----------------------------------------------- | ------ |
| `--avg-daily-sales`   | float | 0.0     | Rata-rata penjualan harian                      | 10.5   |
| `--sales-velocity`    | float | 0.0     | Kecepatan perputaran penjualan                  | 8.2    |
| `--sales-consistency` | float | 1.0     | Konsistensi penjualan (lower = more consistent) | 1.5    |
| `--recent-avg`        | float | 0.0     | Rata-rata penjualan recent period               | 12.0   |
| `--transaction-count` | int   | 0       | Total jumlah transaksi                          | 45     |

### Restock Prediction Parameters

| Parameter             | Type  | Default | Deskripsi                     | Contoh |
| --------------------- | ----- | ------- | ----------------------------- | ------ |
| `--avg-daily-sales`   | float | 0.0     | Rata-rata penjualan harian    | 15.3   |
| `--sales-velocity`    | float | 0.0     | Kecepatan perputaran          | 9.1    |
| `--sales-volatility`  | float | 1.0     | Variabilitas penjualan        | 2.5    |
| `--recent-total`      | float | 0.0     | Total penjualan recent period | 105    |
| `--transaction-count` | int   | 0       | Total jumlah transaksi        | 32     |

### Parameter Calculation Examples

#### 📊 `avg-daily-sales`

```python
# Perhitungan dari Laravel
total_sales_30_days = OutgoingItem::where('item_id', $item_id)
    ->where('outgoing_date', '>=', now()->subDays(30))
    ->sum('quantity');

avg_daily_sales = total_sales_30_days / 30;
```

#### 🚀 `sales-velocity`

```python
# Mengukur percepatan/perlambatan penjualan
recent_7_days = avg_sales_last_7_days
previous_7_days = avg_sales_previous_7_days

sales_velocity = (recent_7_days - previous_7_days) / previous_7_days
```

#### 📈 `sales-consistency`

```python
# Standard deviation dari penjualan harian
daily_sales = [2, 1, 3, 0, 2, 4, 1]  # 7 hari terakhir
sales_consistency = np.std(daily_sales)

# Interpretasi:
# 0.5 = sangat konsisten
# 1.0 = moderately consistent
# 2.0+ = sangat tidak konsisten
```

#### 📊 `sales-volatility`

```python
# Coefficient of variation
mean_sales = np.mean(daily_sales)
std_sales = np.std(daily_sales)
sales_volatility = std_sales / mean_sales

# Interpretasi:
# < 0.5 = volatilitas rendah (predictable)
# 0.5-1.0 = volatilitas sedang
# > 1.0 = volatilitas tinggi (unpredictable)
```

---

## 🎯 Contoh Praktis

### Scenario 1: Sales Prediction "Sepatu Bola Ortus"

#### Input Data

```bash
python main.py predict --type sales --product 1 \
    --avg-daily-sales 1.8 \        # 54 unit dalam 30 hari
    --sales-velocity 0.5 \          # Tumbuh 50% vs periode sebelum
    --sales-consistency 1.2 \       # Agak bervariasi
    --recent-avg 2.1 \              # 14.7 unit dalam 7 hari terakhir
    --transaction-count 25          # 25 transaksi terpisah
```

#### Model Processing

1. **Input Standardization**: Convert ke format yang sama dengan training
2. **Feature Engineering**: Map ke fitur internal model
3. **Ensemble Prediction**: 100 decision trees vote
4. **Result**: Average dari semua votes

#### Decision Trees Voting

```
Tree 1: "Based on avg_daily_sales=1.8 → Predict 2 units"
Tree 2: "Based on recent_avg=2.1 → Predict 2 units"
Tree 3: "Based on sales_velocity=0.5 → Predict 1 unit"
...
Tree 100: "Based on transaction_count=25 → Predict 2 units"

Final Result: (2+2+1+...+2) / 100 = 1.8 → Rounded to 2 units
```

### Scenario 2: Restock Prediction "Kaos Kaki Avo"

#### Current Situation

-   Current stock: 10 unit
-   Daily sales: 15 unit/day
-   Days until stockout: 10 ÷ 15 = 0.67 days (urgent!)

#### Input Command

```bash
python main.py predict --type restock --product 2 \
    --avg-daily-sales 15.0 \        # High demand product
    --sales-velocity 9.1 \           # Sangat fast moving
    --sales-volatility 2.5 \         # Penjualan tidak stabil
    --recent-total 105 \             # 105 unit terjual 7 hari terakhir
    --transaction-count 32           # Populer (banyak customer)
```

#### Model Logic

```python
# Random Forest considers:
high_volatility = 2.5  # → Need safety stock
fast_moving = 9.1       # → Large restock quantity
high_demand = 15.0      # → Frequent restocking needed

# Trees vote:
Tree 1: "High volatility → 200 units (safety stock)"
Tree 2: "Fast moving → 180 units (2 weeks supply)"
Tree 3: "High demand → 210 units (14 days worth)"
...

Final: Average = 195 units → Rounded to 195 units
```

### Scenario 3: Fallback System (Produk Baru)

Jika produk tidak ada dalam training data:

#### Sales Fallback

```python
def generate_fallback_prediction(prediction_type="sales", **kwargs):
    if prediction_type == "sales":
        prev_sales = kwargs.get("prev_sales", 0)
        avg_sales = kwargs.get("avg_sales", 5)

        if prev_sales > 0:
            return max(1, int(prev_sales * 0.9))  # Asumsi turun 10%
        else:
            return max(1, int(avg_sales))         # Gunakan rata-rata
```

#### Restock Fallback

```python
def generate_fallback_prediction(prediction_type="restock", **kwargs):
    if prediction_type == "restock":
        current_stock = kwargs.get("current_stock", 0)
        avg_sales = kwargs.get("avg_sales", 5)

        # Restock jika stock < 7 hari sales
        if current_stock < avg_sales * 7:
            return max(10, int(avg_sales * 14))   # 14 hari worth
        else:
            return 0                              # Tidak perlu restock
```

---

## ⚙️ Tips Optimasi

### 🚀 Performance Tuning

#### Untuk Akurasi Lebih Tinggi

```python
# utils.py - ConfigUtils
"N_ESTIMATORS": 200,    # Dari 100 → 200
"MAX_DEPTH": 10,        # Dari 8 → 10
"MIN_SAMPLES": 5,       # Dari 3 → 5 (data lebih berkualitas)
```

#### Untuk Training Lebih Cepat

```python
"N_ESTIMATORS": 50,     # Dari 100 → 50
"MAX_DEPTH": 6,         # Dari 8 → 6
"N_JOBS": -1,           # Gunakan semua CPU cores
```

#### Untuk Dataset Kecil

```python
"MIN_SAMPLES": 2,       # Dari 3 → 2 (lebih toleran)
"MAX_DEPTH": 6,         # Kurangi complexity
"N_ESTIMATORS": 150,    # Kompensasi dengan lebih banyak trees
```

### 📊 Feature Engineering Advanced

#### Tambah Seasonal Features

```python
# Dalam FeatureUtils.create_sales_features()
features['day_of_week'] = data['tgl'].dt.dayofweek
features['month'] = data['tgl'].dt.month
features['is_weekend'] = (data['tgl'].dt.dayofweek >= 5).astype(int)
features['is_month_end'] = (data['tgl'].dt.day >= 25).astype(int)
```

#### Tambah Trend Features

```python
# Trend penjualan
features['sales_trend_7d'] = features['avg_sales_7'].pct_change()
features['sales_acceleration'] = features['sales_trend_7d'].diff()

# Volatility features
features['sales_volatility'] = (
    features.groupby('id_item')['qty_sold']
    .rolling(7)
    .std()
    .reset_index(0, drop=True)
)
```

### 🎯 Model Ensemble Advanced

#### Multiple Algorithms

```python
from sklearn.ensemble import GradientBoostingRegressor, ExtraTreesRegressor
from sklearn.linear_model import LinearRegression

# Ensemble dari multiple algorithms
models = {
    'rf': RandomForestRegressor(n_estimators=200),
    'gb': GradientBoostingRegressor(n_estimators=100),
    'et': ExtraTreesRegressor(n_estimators=150),
}

# Weighted average predictions
final_prediction = (
    0.5 * rf_prediction +
    0.3 * gb_prediction +
    0.2 * et_prediction
)
```

### 📈 Model Monitoring

#### Accuracy Tracking

```php
// Laravel - PredictionMonitor
class PredictionMonitor {
    public function trackAccuracy($productId, $predicted, $actual) {
        $accuracy = 1 - abs($predicted - $actual) / max($predicted, $actual);

        StockPrediction::where('product_id', $productId)
            ->update(['actual' => $actual, 'accuracy' => $accuracy]);

        // Alert jika accuracy turun
        if ($accuracy < 0.7) {
            Log::warning("Low accuracy for product {$productId}: {$accuracy}");
        }
    }
}
```

#### Auto Retraining

```php
// Retrain model jika accuracy drop
if ($overall_accuracy < 0.75) {
    dispatch(new TrainStockPredictionModel());
}
```

---

## 📊 Parameter Summary Table

### Model Parameters

| Parameter      | Current | Recommended | Impact            |
| -------------- | ------- | ----------- | ----------------- |
| `N_ESTIMATORS` | 100     | 200-300     | ↑ Akurasi, ↑ Time |
| `MAX_DEPTH`    | 8       | 10-12       | ↑ Complexity      |
| `MIN_SAMPLES`  | 3       | 5-10        | ↑ Quality         |
| `RANDOM_STATE` | 42      | 42          | Consistency       |

### Feature Parameters (Auto-generated)

| Feature        | Description            | Example   | Time Window |
| -------------- | ---------------------- | --------- | ----------- |
| `prev_sales_1` | Yesterday sales        | 5 units   | 1 day       |
| `prev_sales_7` | Last week same day     | 3 units   | 7 days      |
| `avg_sales_7`  | 7-day rolling average  | 4.2 units | 7 days      |
| `avg_sales_30` | 30-day rolling average | 3.8 units | 30 days     |

### Input Parameters (From Laravel)

| Parameter           | Sales | Restock | Description            |
| ------------------- | ----- | ------- | ---------------------- |
| `avg-daily-sales`   | ✅    | ✅      | Daily average sales    |
| `sales-velocity`    | ✅    | ✅      | Sales growth rate      |
| `sales-consistency` | ✅    | ❌      | Sales stability        |
| `sales-volatility`  | ❌    | ✅      | Sales variability      |
| `recent-avg`        | ✅    | ❌      | Recent period average  |
| `recent-total`      | ❌    | ✅      | Recent period total    |
| `transaction-count` | ✅    | ✅      | Number of transactions |

---

## 🔗 File References

-   **Main Script**: `scripts/main.py`
-   **Model Logic**: `scripts/stock/predictor.py`
-   **Utilities**: `scripts/stock/utils.py`
-   **Laravel Job**: `app/Jobs/TrainStockPredictionModel.php`
-   **Models**: `app/Models/StockPrediction.php`

---

_Dokumentasi ini menjelaskan semua parameter yang digunakan dalam sistem prediksi Random Forest. Untuk pertanyaan lebih lanjut, silakan merujuk ke kode sumber atau hubungi tim development._
