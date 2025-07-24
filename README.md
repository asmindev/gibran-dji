# 📦 Inventory Analysis System

A comprehensive Laravel-based inventory management system with integrated machine learning analysis using Python. This system provides association rule mining (Apriori algorithm) and demand prediction (Random Forest) capabilities.

## 🚀 Features

### Core Inventory Management

-   **Dashboard**: Real-time inventory overview with key metrics
-   **Item Management**: Complete CRUD operations for inventory items
-   **Category Management**: Organize items by categories
-   **Incoming Items**: Track inventory additions with import/export capabilities
-   **Outgoing Items**: Monitor inventory usage and distribution
-   **Reports**: Comprehensive reporting system

### 🧠 Machine Learning Analysis

-   **Association Rule Mining**: Discover patterns in item combinations using Apriori algorithm
-   **Demand Prediction**: Forecast future demand using Random Forest regression
-   **Analysis Dashboard**: Visualize insights and recommendations
-   **Automated Processing**: Scheduled analysis via Artisan commands

## 🛠 Technical Stack

### Backend

-   **Laravel 11**: PHP framework for web application
-   **MySQL**: Database management
-   **PHP 8.3+**: Server-side programming

### Machine Learning

-   **Python 3.13**: ML script runtime
-   **pandas**: Data manipulation and analysis
-   **scikit-learn**: Machine learning algorithms (Random Forest)
-   **mlxtend**: Association rule mining (Apriori)
-   **numpy**: Numerical computing

### Frontend

-   **Tailwind CSS**: Utility-first CSS framework
-   **Alpine.js**: Lightweight JavaScript framework
-   **Blade Templates**: Laravel templating engine

## 📋 Installation

### Prerequisites

-   PHP 8.3 or higher
-   Composer
-   Node.js & npm
-   MySQL 8.0+
-   Python 3.13
-   Git

### Step 1: Clone Repository

```bash
git clone <repository-url>
cd gibran
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

### Step 3: Install Node Dependencies

```bash
npm install
```

### Step 4: Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### Step 5: Database Configuration

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gibran_inventory
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 6: Run Migrations

```bash
php artisan migrate
```

### Step 7: Generate Sample Data (Optional)

```bash
php artisan db:seed --class=InventoryAnalysisSampleSeeder
```

### Step 8: Python Environment Setup

```bash
# Create virtual environment
python3 -m venv .venv
source .venv/bin/activate  # On Windows: .venv\Scripts\activate

# Install Python packages
pip install pandas numpy scikit-learn mlxtend
```

### Step 9: Build Assets

```bash
npm run build
```

### Step 10: Start Development Server

```bash
php artisan serve
```

Visit: `http://localhost:8000`

## 🔧 Configuration

### Python Path Configuration

Ensure the service uses the correct Python path in `app/Services/InventoryAnalysisService.php`:

```php
$pythonPath = base_path('.venv/bin/python');
```

### Storage Permissions

```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

## 📊 Machine Learning Analysis

### Association Rule Mining (Apriori)

-   **Purpose**: Discover patterns in item combinations
-   **Algorithm**: Apriori algorithm with configurable support and confidence thresholds
-   **Output**: Rules showing "if customers buy X, they also buy Y"
-   **Metrics**: Support, Confidence, Lift

### Demand Prediction (Random Forest)

-   **Purpose**: Forecast future demand for items
-   **Algorithm**: Random Forest Regression
-   **Features**: Day of week, month, rolling averages, lag features
-   **Output**: Predicted demand with confidence scores

### Running Analysis

#### Via Web Interface

1. Navigate to "Dashboard Analisis"
2. Click "Jalankan Analisis"
3. View results in recommendations and predictions sections

#### Via Artisan Command

```bash
# Dry run (preview without execution)
php artisan inventory:analyze --dry-run

# Force analysis (ignore recent analysis check)
php artisan inventory:analyze --force

# Clean up old data
php artisan inventory:analyze --cleanup
```

#### Manual Python Script

```bash
cd /path/to/project
.venv/bin/python scripts/analyze_inventory.py input.csv output_directory/
```

### Analysis Workflow

1. **Data Export**: Transaction data exported to CSV
2. **Python Analysis**: ML algorithms process the data
3. **Results Import**: Analysis results imported back to database
4. **Visualization**: Results displayed in web interface

## 📁 Project Structure

```
├── app/
│   ├── Console/Commands/
│   │   └── InventoryAnalyzeCommand.php    # Artisan command
│   ├── Http/Controllers/
│   │   ├── AnalysisController.php         # Analysis dashboard
│   │   ├── OutgoingItemController.php     # Extended with analysis
│   │   └── ...
│   ├── Models/
│   │   ├── InventoryRecommendation.php    # Association rules
│   │   ├── StockPrediction.php           # Demand predictions
│   │   └── ...
│   └── Services/
│       └── InventoryAnalysisService.php   # Core analysis service
├── database/migrations/
│   ├── *_create_inventory_recommendations_table.php
│   └── *_create_stock_predictions_table.php
├── resources/views/
│   ├── analysis/
│   │   └── index.blade.php               # Analysis dashboard
│   └── ...
├── scripts/
│   └── analyze_inventory.py              # Python ML script
└── routes/
    └── web.php                           # Analysis routes
```

## 🎯 Usage Examples

### Creating Analysis Data

1. Add items to inventory
2. Record outgoing transactions
3. Run analysis when sufficient data exists

### Viewing Results

-   **Recommendations**: Items frequently bought together
-   **Predictions**: Forecasted demand for upcoming periods
-   **Confidence Scores**: Reliability metrics for each insight

### Scheduling Analysis

Add to Laravel Scheduler in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('inventory:analyze')->daily();
}
```

## 🔍 API Endpoints

### Analysis Routes

-   `GET /analysis` - Analysis dashboard
-   `GET /analysis/recommendations` - View recommendations
-   `GET /analysis/predictions` - View predictions
-   `POST /analysis/run` - Run analysis manually

### Import/Export Routes

-   `GET /outgoing_items/export` - Export transaction data
-   `POST /outgoing_items/import-recommendations` - Import recommendations
-   `POST /outgoing_items/import-predictions` - Import predictions

## 🧪 Testing

### Unit Tests

```bash
php artisan test
```

### Python Script Testing

```bash
# Test with sample data
.venv/bin/python scripts/analyze_inventory.py storage/app/test_transactions.csv storage/app/test_output/
```

## 📈 Performance Considerations

### Database Optimization

-   Index on `analyzed_at` columns for faster queries
-   Regular cleanup of old analysis data
-   Pagination for large result sets

### Python Performance

-   Configurable analysis parameters (support, confidence thresholds)
-   Memory-efficient data processing
-   Error handling for large datasets

### Scalability

-   Background job processing for large analyses
-   Caching of analysis results
-   Incremental analysis for frequent updates

## 🛡 Security

### Input Validation

-   File upload restrictions (CSV only, size limits)
-   SQL injection prevention via Eloquent ORM
-   CSRF protection on all forms

### Access Control

-   Laravel authentication and authorization
-   Admin-only access to analysis functions
-   Secure file handling

## 🐛 Troubleshooting

### Common Issues

#### Python Import Errors

```bash
# Reinstall packages
pip install --upgrade pandas numpy scikit-learn mlxtend
```

#### Permission Denied

```bash
chmod +x scripts/analyze_inventory.py
chmod -R 775 storage/
```

#### Memory Issues

-   Reduce dataset size for initial testing
-   Adjust Python script parameters
-   Monitor server resources

### Debug Mode

Enable Laravel debug mode in `.env`:

```env
APP_DEBUG=true
```

### Logs

Check Laravel logs:

```bash
tail -f storage/logs/laravel.log
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -am 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Create Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👥 Support

For support and questions:

-   Create an issue in the repository
-   Contact the development team
-   Check the documentation

## 🔄 Updates

### Version History

-   **v1.0.0**: Initial release with basic inventory management
-   **v2.0.0**: Added machine learning analysis capabilities
-   **v2.1.0**: Enhanced UI and analysis dashboard

### Upcoming Features

-   Real-time analysis dashboard
-   Advanced visualization charts
-   API for external integrations
-   Mobile application support

---

**Made with ❤️ for efficient inventory management**
