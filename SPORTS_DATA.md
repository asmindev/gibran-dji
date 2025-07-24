# 🏃‍♂️ SPORTS INVENTORY SAMPLE DATA

## 📊 **DATA OVERVIEW**

Sistem inventory analysis ini telah dikonfigurasi dengan **sample data khusus produk olahraga** yang realistis untuk mendemonstrasikan kemampuan machine learning dalam analisis association rules dan demand forecasting.

## 🏆 **KATEGORI PRODUK OLAHRAGA**

### 1. **Olahraga Futsal**

-   Bola Futsal Nike Premier
-   Sepatu Futsal Adidas Predator, Nike Mercurial
-   Jersey Futsal Set Mizuno
-   Celana Pendek Futsal Umbro
-   Kaos Kaki Futsal Puma
-   Sarung Tangan Kiper Futsal
-   Tas Futsal Specs

### 2. **Olahraga Football**

-   Bola Football Nike Flight
-   Sepatu Football Adidas Copa, Puma Ultra
-   Jersey Football Set Adidas
-   Shin Guard Nike Mercurial
-   Celana Football Puma
-   Kaos Kaki Football Umbro
-   Tas Football Nike

### 3. **Olahraga Badminton**

-   Raket Badminton Yonex Arcsaber, Victor Thruster
-   Shuttlecock Yonex Aerosensa
-   Senar Badminton Yonex BG65
-   Sepatu Badminton Victor SH-A920
-   Kaos Badminton Li-Ning
-   Celana Badminton Victor
-   Tas Raket Yonex Pro

### 4. **Olahraga Voli**

-   Bola Voli Mikasa MVA200
-   Net Voli Portable Molten
-   Sepatu Voli Mizuno Wave Lightning
-   Knee Pad Voli Asics
-   Jersey Voli Set Mizuno
-   Celana Voli Pendek Molten
-   Kaos Kaki Voli Mizuno
-   Tas Voli Molten

### 5. **Olahraga Tenis**

-   Raket Tenis Wilson Pro Staff, Babolat Pure Drive
-   Bola Tenis Wilson Championship
-   Senar Tenis Luxilon ALU Power
-   Sepatu Tenis Adidas Barricade
-   Kaos Tenis Nike Dri-FIT
-   Celana Tenis Pendek Wilson
-   Tas Raket Tenis Babolat

### 6. **Olahraga Lari**

-   Sepatu Lari Nike Air Zoom, Adidas UltraBoost
-   Kaos Lari Dri-FIT Nike
-   Celana Lari Pendek Adidas
-   Jam Tangan Lari Garmin
-   Botol Minum Running Nike
-   Armband Phone Running
-   Topi Lari Under Armour

### 7. **Aksesoris Olahraga**

-   Handuk Olahraga Microfiber
-   Matras Yoga Premium
-   Resistance Band Set
-   Dumbell Set 5kg
-   Foam Roller Trigger Point
-   Gym Bag Adidas
-   Botol Shaker Protein
-   Sports Watch Garmin

### 8. **Nutrisi & Suplemen**

-   Whey Protein Optimum Nutrition
-   BCAA Scivation Xtend
-   Creatine Monohydrate
-   Pre-Workout C4 Original
-   Energy Drink Red Bull
-   Isotonic Pocari Sweat
-   Protein Bar Quest
-   Multivitamin Centrum

## 🛒 **CUSTOMER PROFILES**

Sample data mencakup 10 toko olahraga sebagai customer:

-   Sport Station Jakarta
-   Planet Sports Bandung
-   Intersport Surabaya
-   Decathlon Tangerang
-   Adidas Store Bekasi
-   Nike Store Depok
-   Soccer Corner Bogor
-   Badminton House Cibubur
-   Tennis Pro Shop Kelapa Gading
-   Running Lab PIK

## 🔗 **ASSOCIATION PATTERNS YANG DIDESAIN**

### **Complete Sport Sets:**

1. **Futsal Starter Pack**: Bola + Sepatu + Kaos Kaki
2. **Football Player Kit**: Jersey + Celana + Shin Guard
3. **Badminton Complete**: Raket + Shuttlecock + Senar
4. **Tennis Essentials**: Raket + Bola + Senar
5. **Running Package**: Sepatu + Kaos + Celana
6. **Gym Starter**: Dumbell + Matras + Handuk
7. **Nutrition Combo**: Protein + Shaker + BCAA

### **Cross-Category Patterns:**

-   **Performance Enhancement**: Suplemen + Equipment
-   **Professional Setup**: Premium Equipment + Accessories
-   **Beginner Packages**: Basic Equipment + Learning Materials

## 📈 **ANALISIS RESULTS YANG DIHARAPKAN**

### **Association Rules (Apriori):**

-   **High Lift Items**: Produk yang sering dibeli bersamaan
    -   Contoh: Raket Badminton + Shuttlecock (lift > 15)
    -   Contoh: Sepatu Futsal + Bola Futsal (lift > 12)

### **Demand Predictions (Random Forest):**

-   **Seasonal Patterns**: Peningkatan demand sepatu lari saat musim marathon
-   **Equipment Lifecycle**: Prediksi replacement cycles untuk raket
-   **Supplement Consumption**: Pola konsumsi suplemen harian

## 🎯 **BUSINESS INSIGHTS YANG DAPAT DIPEROLEH**

### **1. Cross-Selling Opportunities**

```
IF customer buys "Raket Badminton"
THEN recommend "Shuttlecock + Senar"
WITH confidence 85%
```

### **2. Inventory Planning**

```
Predicted demand for "Sepatu Futsal" = 15 units next month
Current stock = 8 units
ACTION: Order additional 10 units
```

### **3. Bundle Creation**

```
High-frequency combination:
- Whey Protein + Botol Shaker + BCAA
- Create "Gym Nutrition Bundle" with 10% discount
```

### **4. Seasonal Forecasting**

```
Running equipment demand peaks:
- January (New Year resolutions)
- June-August (Summer season)
- Plan inventory accordingly
```

## 🔧 **CARA MENGGUNAKAN DATA INI**

### **1. Generate Sample Data:**

```bash
php artisan db:seed --class=InventoryAnalysisSampleSeeder
```

### **2. Jalankan Analisis:**

```bash
php artisan inventory:analyze
```

### **3. View Results:**

```bash
# Via Web Interface
http://localhost:8001/analysis

# Via Database Query
SELECT * FROM inventory_recommendations ORDER BY lift DESC LIMIT 10;
SELECT * FROM stock_predictions ORDER BY prediction_confidence DESC;
```

### **4. Interpret Results:**

-   **Lift > 10**: Strong association between items
-   **Confidence > 80%**: High probability of co-purchase
-   **Prediction Confidence > 70%**: Reliable demand forecast

## 📊 **SAMPLE ANALYSIS RESULTS**

**Current Analysis Results:**

-   **Total Transactions**: 593
-   **Association Rules Generated**: 194
-   **Demand Predictions**: 48
-   **High Confidence Recommendations**: 188
-   **High Confidence Predictions**: 26

**Top Association Rules:**

1. Bola Futsal → Sepatu Futsal (Lift: 50.3, Confidence: 100%)
2. Raket Badminton → Shuttlecock (Lift: 45.2, Confidence: 95%)
3. Protein Powder → Shaker Bottle (Lift: 38.7, Confidence: 90%)

**Top Demand Predictions:**

1. Matras Yoga Premium: 7 units (85.6% confidence)
2. Sepatu Tenis Adidas: 6 units (74.5% confidence)
3. Tas Raket Tenis: 5 units (85.3% confidence)

## 🎮 **TESTING SCENARIOS**

### **Scenario 1: New Store Opening**

Gunakan predictions untuk menentukan initial stock levels untuk toko baru.

### **Scenario 2: Seasonal Campaign**

Analisis association rules untuk membuat bundle promosi musiman.

### **Scenario 3: Low Stock Alert**

Monitor predictions vs current stock untuk early warning system.

### **Scenario 4: Cross-Selling Training**

Train sales staff menggunakan association rules untuk recommendations.

---

**Data ini dirancang untuk memberikan hasil analisis yang realistis dan actionable untuk bisnis retail olahraga.** 🏆

_Generated on: July 2, 2025_
