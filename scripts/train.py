# train_model.py - Professional Stock Prediction Model Training System
"""
Advanced Stock Prediction Model Training System

This module provides comprehensive training capabilities for stock prediction models
using Random Forest algorithms with time series cross-validation.

Features:
- Dual prediction modes (daily/monthly)
- Comprehensive data validation and preprocessing
- Advanced feature engineering with lag variables
- Time series cross-validation for robust evaluation
- Detailed performance metrics and analysis
- Professional logging and error handling
- Model persistence and metadata tracking

Author: Stock Prediction System
Version: 2.0
Date: August 2025
"""

import os
import sys
import pandas as pd
import numpy as np
from sklearn.compose import ColumnTransformer
from sklearn.preprocessing import OneHotEncoder, StandardScaler
from sklearn.pipeline import Pipeline
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import TimeSeriesSplit
from sklearn.metrics import (
    mean_absolute_error,
    r2_score,
    mean_squared_error,
    mean_absolute_percentage_error,
)
import joblib
import warnings
import json
from datetime import datetime, timedelta
from typing import Dict, Tuple, List, Optional

# Suppress warnings for cleaner output
warnings.filterwarnings("ignore")

# Global configuration
CONFIG = {
    "MODEL_VERSION": "2.0",
    "TRAINING_DATE": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
    "MIN_SAMPLES_DAILY": 5,
    "MIN_SAMPLES_MONTHLY": 3,
    "RANDOM_STATE": 42,
    "CV_SPLITS": 3,
    "LOG_LEVEL": "INFO",
}


def log_message(message: str, level: str = "INFO", indent: int = 0) -> None:
    """
    Professional logging function with enhanced formatting

    Args:
        message: Message to log
        level: Log level (INFO, WARNING, ERROR, SUCCESS, DEBUG)
        indent: Indentation level for hierarchical logging
    """
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    # Color coding for different log levels
    colors = {
        "INFO": "\033[36m",  # Cyan
        "SUCCESS": "\033[32m",  # Green
        "WARNING": "\033[33m",  # Yellow
        "ERROR": "\033[31m",  # Red
        "DEBUG": "\033[35m",  # Magenta
        "RESET": "\033[0m",  # Reset
    }

    # Icons for different log levels
    icons = {"INFO": "ℹ️", "SUCCESS": "✅", "WARNING": "⚠️", "ERROR": "❌", "DEBUG": "🔍"}

    color = colors.get(level, colors["INFO"])
    icon = icons.get(level, "📝")
    indent_str = "  " * indent

    # Format message with proper structure
    formatted_message = (
        f"{color}[{timestamp}] {icon} {level}: {indent_str}{message}{colors['RESET']}"
    )
    print(formatted_message)


def print_header(title: str, width: int = 80, char: str = "=") -> None:
    """Print a formatted header for sections"""
    print("\n" + char * width)
    print(f"{title:^{width}}")
    print(char * width)


def print_subheader(title: str, width: int = 60, char: str = "-") -> None:
    """Print a formatted subheader for subsections"""
    print(f"\n{char * width}")
    print(f"📊 {title}")
    print(char * width)


def validate_data_folder(data_folder: str = "data") -> List[str]:
    """
    Validate data folder and return Excel files with comprehensive checks

    Args:
        data_folder: Path to data folder

    Returns:
        List of valid Excel file names

    Raises:
        FileNotFoundError: If folder or files not found
    """
    log_message("🔍 Validating data folder structure...", "INFO")

    if not os.path.exists(data_folder):
        raise FileNotFoundError(f"Data folder '{data_folder}' tidak ditemukan")

    # Get all Excel files
    all_files = os.listdir(data_folder)
    excel_files = [f for f in all_files if f.endswith((".xlsx", ".xls"))]

    if not excel_files:
        raise FileNotFoundError(
            f"Tidak ada file Excel ditemukan di folder '{data_folder}'"
        )

    # Detailed file analysis
    log_message(f"📁 Folder: {os.path.abspath(data_folder)}", "INFO", 1)
    log_message(f"📄 Total files: {len(all_files)}", "INFO", 1)
    log_message(f"📋 Excel files: {len(excel_files)}", "SUCCESS", 1)

    for i, file in enumerate(excel_files, 1):
        file_path = os.path.join(data_folder, file)
        file_size = os.path.getsize(file_path) / 1024  # KB
        log_message(f"   {i}. {file} ({file_size:.1f} KB)", "INFO", 2)

    return excel_files


def analyze_data_quality(df: pd.DataFrame, file_name: str) -> Dict:
    """
    Analyze data quality metrics for a loaded file

    Args:
        df: DataFrame to analyze
        file_name: Name of the file being analyzed

    Returns:
        Dictionary with quality metrics
    """
    quality_metrics = {
        "file_name": file_name,
        "total_rows": len(df),
        "total_columns": len(df.columns),
        "missing_values": df.isnull().sum().sum(),
        "duplicate_rows": df.duplicated().sum(),
        "memory_usage_mb": df.memory_usage(deep=True).sum() / 1024 / 1024,
    }

    # Check for required columns
    expected_cols = ["no", "id_trx", "tgl", "nama_barang", "kategori", "jumlah"]
    quality_metrics["has_required_columns"] = all(
        col in df.columns for col in expected_cols[: len(df.columns)]
    )

    return quality_metrics


def convert_indonesian_date(date_str):
    """Convert Indonesian month names to English with error handling"""
    month_mapping = {
        "Januari": "January",
        "Februari": "February",
        "Maret": "March",
        "April": "April",
        "Mei": "May",
        "Juni": "June",
        "Juli": "July",
        "Agustus": "August",
        "September": "September",
        "Oktober": "October",
        "November": "November",
        "Desember": "December",
    }

    if pd.isna(date_str):
        return date_str

    try:
        date_str = str(date_str)
        for indo_month, eng_month in month_mapping.items():
            date_str = date_str.replace(indo_month, eng_month)
        return date_str
    except Exception as e:
        log_message(f"Error converting date '{date_str}': {e}", "WARNING")
        return date_str


def load_and_process_data(data_folder: str = "data") -> pd.DataFrame:
    """
    Load and process all Excel files with comprehensive error handling and quality analysis

    Args:
        data_folder: Path to folder containing Excel files

    Returns:
        Combined DataFrame with all loaded data

    Raises:
        ValueError: If no files could be loaded successfully
    """
    print_subheader("DATA LOADING & QUALITY ANALYSIS")

    try:
        files = validate_data_folder(data_folder)
        all_df = []
        quality_reports = []

        log_message("🚀 Starting Excel file processing...", "INFO")

        for i, file in enumerate(files, 1):
            try:
                file_path = os.path.join(data_folder, file)
                log_message(f"📂 Loading file {i}/{len(files)}: {file}", "INFO", 1)

                # Read Excel with comprehensive error handling
                df = pd.read_excel(file_path, skiprows=1)

                # Analyze data quality
                quality_metrics = analyze_data_quality(df, file)
                quality_reports.append(quality_metrics)

                # Validate column structure
                expected_cols = [
                    "no",
                    "id_trx",
                    "tgl",
                    "nama_barang",
                    "kategori",
                    "jumlah",
                ]

                if len(df.columns) < 6:
                    log_message(
                        f"Column mismatch: Expected 6, got {len(df.columns)}",
                        "WARNING",
                        2,
                    )
                    continue

                df.columns = expected_cols[: len(df.columns)]

                # Basic data validation
                if df.empty:
                    log_message("File is empty, skipping...", "WARNING", 2)
                    continue

                # Check for required columns
                required_cols = ["nama_barang", "kategori", "jumlah"]
                missing_required = [
                    col for col in required_cols if col not in df.columns
                ]

                if missing_required:
                    log_message(
                        f"Missing required columns: {missing_required}", "WARNING", 2
                    )
                    continue

                all_df.append(df)
                log_message(
                    f"✓ Successfully loaded: {len(df):,} rows | "
                    f"Columns: {len(df.columns)} | "
                    f"Missing: {df.isnull().sum().sum()}",
                    "SUCCESS",
                    2,
                )

            except Exception as e:
                log_message(f"❌ Failed to load {file}: {str(e)[:100]}...", "ERROR", 2)
                continue

        # Validate that we have data to work with
        if not all_df:
            raise ValueError("❌ No files were successfully loaded")

        # Display quality summary
        print_subheader("DATA QUALITY SUMMARY")
        total_rows = sum(df.shape[0] for df in all_df)
        total_memory = sum(q["memory_usage_mb"] for q in quality_reports)

        log_message(f"📊 Files processed: {len(all_df)}/{len(files)}", "SUCCESS", 1)
        log_message(f"📊 Total rows: {total_rows:,}", "INFO", 1)
        log_message(f"💾 Memory usage: {total_memory:.2f} MB", "INFO", 1)

        # Combine all dataframes
        log_message("🔄 Combining all datasets...", "INFO")
        combined_df = pd.concat(all_df, ignore_index=True)

        log_message(
            f"✅ Data combination successful: {len(combined_df):,} total rows",
            "SUCCESS",
            1,
        )

        return combined_df

    except Exception as e:
        log_message(f"💥 Fatal error in data loading: {e}", "ERROR")
        raise


def process_sales_data(df: pd.DataFrame) -> pd.DataFrame:
    """
    Process sales data with comprehensive validation, cleaning, and analysis

    Args:
        df: Raw combined DataFrame

    Returns:
        Cleaned sales DataFrame

    Raises:
        ValueError: If no valid sales data found
    """
    print_subheader("SALES DATA PROCESSING & ANALYSIS")

    try:
        log_message("🔍 Starting sales data processing pipeline...", "INFO")

        # Validate required columns
        required_cols = ["kategori", "jumlah", "nama_barang", "tgl"]
        missing_cols = [col for col in required_cols if col not in df.columns]
        if missing_cols:
            raise ValueError(f"Missing required columns: {missing_cols}")

        log_message(f"✅ Column validation passed: {required_cols}", "SUCCESS", 1)

        # Analyze transaction categories
        log_message("📊 Analyzing transaction categories...", "INFO", 1)
        category_analysis = df["kategori"].value_counts()

        for category, count in category_analysis.head(10).items():
            percentage = (count / len(df)) * 100
            log_message(f"   • {category}: {count:,} ({percentage:.1f}%)", "INFO", 2)

        # Filter sales transactions (keluar)
        log_message(
            "🔽 Filtering sales transactions (kategori: 'keluar')...", "INFO", 1
        )
        sales_mask = df["kategori"].str.lower().str.contains("keluar", na=False)
        sales = df[sales_mask].copy()

        if sales.empty:
            raise ValueError("❌ No sales transactions found (kategori 'keluar')")

        sales_percentage = (len(sales) / len(df)) * 100
        log_message(
            f"✅ Sales filter applied: {len(sales):,}/{len(df):,} transactions ({sales_percentage:.1f}%)",
            "SUCCESS",
            1,
        )

        # Process quantity data
        log_message("🔢 Processing quantity data...", "INFO", 1)
        sales = sales.rename(columns={"jumlah": "qty_sold"})

        # Convert to numeric and analyze
        original_count = len(sales)
        sales["qty_sold"] = pd.to_numeric(sales["qty_sold"], errors="coerce")
        sales = sales.dropna(subset=["qty_sold"])
        numeric_loss = original_count - len(sales)

        if numeric_loss > 0:
            log_message(
                f"⚠️ Removed {numeric_loss} rows with invalid quantities", "WARNING", 2
            )

        # Remove zero or negative sales
        positive_sales = sales[sales["qty_sold"] > 0]
        negative_loss = len(sales) - len(positive_sales)
        sales = positive_sales

        if negative_loss > 0:
            log_message(
                f"⚠️ Removed {negative_loss} rows with zero/negative quantities",
                "WARNING",
                2,
            )

        # Quantity statistics
        qty_stats = {
            "count": len(sales),
            "mean": sales["qty_sold"].mean(),
            "median": sales["qty_sold"].median(),
            "std": sales["qty_sold"].std(),
            "min": sales["qty_sold"].min(),
            "max": sales["qty_sold"].max(),
            "q25": sales["qty_sold"].quantile(0.25),
            "q75": sales["qty_sold"].quantile(0.75),
        }

        log_message("📈 Quantity Statistics:", "INFO", 2)
        log_message(f"   • Valid records: {qty_stats['count']:,}", "INFO", 3)
        log_message(f"   • Mean: {qty_stats['mean']:.2f} units", "INFO", 3)
        log_message(f"   • Median: {qty_stats['median']:.2f} units", "INFO", 3)
        log_message(
            f"   • Range: {qty_stats['min']:.0f} - {qty_stats['max']:.0f} units",
            "INFO",
            3,
        )
        log_message(f"   • Std Dev: {qty_stats['std']:.2f}", "INFO", 3)

        # Process dates
        log_message("📅 Processing date information...", "INFO", 1)
        sales["tgl"] = sales["tgl"].apply(convert_indonesian_date)

        try:
            sales["tgl"] = pd.to_datetime(sales["tgl"], format="mixed", dayfirst=True)
        except Exception as e:
            log_message(f"⚠️ Date parsing warning: {e}", "WARNING", 2)
            log_message("🔄 Trying alternative date parsing methods...", "INFO", 2)
            sales["tgl"] = pd.to_datetime(sales["tgl"], errors="coerce", dayfirst=True)

        # Remove invalid dates
        valid_dates = sales.dropna(subset=["tgl"])
        date_loss = len(sales) - len(valid_dates)
        sales = valid_dates

        if date_loss > 0:
            log_message(f"⚠️ Removed {date_loss} rows with invalid dates", "WARNING", 2)

        if sales.empty:
            raise ValueError("❌ No data remaining after date validation")

        # Date range analysis
        min_date = sales["tgl"].min()
        max_date = sales["tgl"].max()
        date_range_days = (max_date - min_date).days

        log_message("📊 Date Range Analysis:", "SUCCESS", 2)
        log_message(f"   • Start date: {min_date.strftime('%Y-%m-%d (%A)')}", "INFO", 3)
        log_message(f"   • End date: {max_date.strftime('%Y-%m-%d (%A)')}", "INFO", 3)
        log_message(f"   • Coverage: {date_range_days} days", "INFO", 3)
        log_message(
            f"   • Months: {min_date.strftime('%B %Y')} - {max_date.strftime('%B %Y')}",
            "INFO",
            3,
        )

        # Product analysis
        log_message("🏷️ Analyzing product portfolio...", "INFO", 1)
        product_stats = (
            sales.groupby("nama_barang")
            .agg({"qty_sold": ["count", "sum", "mean", "std"]})
            .round(2)
        )
        product_stats.columns = ["transactions", "total_sold", "avg_qty", "qty_std"]
        product_stats = product_stats.sort_values("total_sold", ascending=False)

        log_message(
            f"📦 Product Portfolio: {len(product_stats)} unique products", "SUCCESS", 2
        )
        log_message("🏆 Top 10 Products by Total Sales:", "INFO", 2)

        for i, (product, stats) in enumerate(product_stats.head(10).iterrows(), 1):
            product_name = str(product)[:35] if len(str(product)) > 35 else str(product)
            log_message(
                f"   {i:2d}. {product_name:<35} | "
                f"Total: {stats['total_sold']:>6.0f} | "
                f"Txns: {stats['transactions']:>4.0f} | "
                f"Avg: {stats['avg_qty']:>5.1f}",
                "INFO",
                3,
            )

        log_message(f"✅ Sales data processing completed successfully", "SUCCESS")
        log_message(
            f"📊 Final dataset: {len(sales):,} valid sales records", "SUCCESS", 1
        )

        return sales

    except Exception as e:
        log_message(f"💥 Error in sales data processing: {e}", "ERROR")
        raise


def create_features(
    sales: pd.DataFrame, prediction_type: str = "daily"
) -> pd.DataFrame:
    """
    Create sophisticated features for daily or monthly prediction with comprehensive analysis

    Args:
        sales: Processed sales data DataFrame
        prediction_type: 'daily' or 'monthly'

    Returns:
        Feature-engineered DataFrame ready for model training

    Raises:
        ValueError: If prediction_type is invalid or insufficient data
    """
    print_subheader(f"FEATURE ENGINEERING - {prediction_type.upper()} PREDICTION")

    try:
        log_message(
            f"🔧 Starting feature engineering for {prediction_type} prediction...",
            "INFO",
        )

        if prediction_type not in ["daily", "monthly"]:
            raise ValueError("prediction_type must be 'daily' or 'monthly'")

        # Step 1: Daily aggregation
        log_message("📊 Aggregating data to daily level...", "INFO", 1)
        sales["tgl_date"] = sales["tgl"].dt.date
        daily_demand = (
            sales.groupby(["tgl_date", "nama_barang"])["qty_sold"].sum().reset_index()
        )
        daily_demand = daily_demand.rename(columns={"tgl_date": "tgl"})
        daily_demand["tgl"] = pd.to_datetime(daily_demand["tgl"])
        daily_demand = daily_demand.sort_values(["nama_barang", "tgl"])

        # Daily aggregation statistics
        daily_stats = {
            "total_days": daily_demand["tgl"].nunique(),
            "total_products": daily_demand["nama_barang"].nunique(),
            "total_records": len(daily_demand),
            "avg_daily_sales": daily_demand["qty_sold"].mean(),
            "date_range": (daily_demand["tgl"].max() - daily_demand["tgl"].min()).days,
        }

        log_message("📈 Daily Aggregation Summary:", "SUCCESS", 1)
        log_message(f"   • Records: {daily_stats['total_records']:,}", "INFO", 2)
        log_message(f"   • Products: {daily_stats['total_products']:,}", "INFO", 2)
        log_message(f"   • Days covered: {daily_stats['total_days']:,}", "INFO", 2)
        log_message(
            f"   • Avg daily sales: {daily_stats['avg_daily_sales']:.2f} units",
            "INFO",
            2,
        )
        log_message(f"   • Date span: {daily_stats['date_range']} days", "INFO", 2)

        # Initialize final_data variable
        final_data = pd.DataFrame()

        if prediction_type == "daily":
            log_message("🗓️ Building DAILY prediction features...", "INFO", 1)
            log_message("📋 Feature specification:", "INFO", 2)
            log_message("   • lag1: Yesterday's sales", "INFO", 3)
            log_message("   • lag2: Sales from 2 days ago", "INFO", 3)
            log_message("   • lag3: Sales from 3 days ago", "INFO", 3)
            log_message("   • Target: Today's sales prediction", "INFO", 3)

            # Create lag features
            daily_demand["lag1"] = daily_demand.groupby("nama_barang")[
                "qty_sold"
            ].shift(1)
            daily_demand["lag2"] = daily_demand.groupby("nama_barang")[
                "qty_sold"
            ].shift(2)
            daily_demand["lag3"] = daily_demand.groupby("nama_barang")[
                "qty_sold"
            ].shift(3)

            # Select features
            final_data = daily_demand[
                ["nama_barang", "tgl", "qty_sold", "lag1", "lag2", "lag3"]
            ].copy()

            # Data quality check
            initial_rows = len(final_data)
            final_data = final_data.dropna()
            final_rows = len(final_data)
            removed_rows = initial_rows - final_rows

            log_message(f"🧹 Data cleaning results:", "INFO", 2)
            log_message(f"   • Initial rows: {initial_rows:,}", "INFO", 3)
            log_message(f"   • Removed (NaN): {removed_rows:,}", "INFO", 3)
            log_message(f"   • Final rows: {final_rows:,}", "INFO", 3)
            log_message(
                f"   • Retention rate: {(final_rows/initial_rows)*100:.1f}%", "INFO", 3
            )

        elif prediction_type == "monthly":
            log_message("📅 Building MONTHLY prediction features...", "INFO", 1)
            log_message("📋 Feature specification:", "INFO", 2)
            log_message(
                "   • prev_month_total: Previous month's total sales", "INFO", 3
            )
            log_message(
                "   • Target: Current month's total sales prediction", "INFO", 3
            )

            # Aggregate to monthly level
            daily_demand["year_month"] = daily_demand["tgl"].dt.to_period("M")
            monthly_demand = (
                daily_demand.groupby(["nama_barang", "year_month"])["qty_sold"]
                .sum()
                .reset_index()
            )
            monthly_demand = monthly_demand.sort_values(["nama_barang", "year_month"])

            # Monthly aggregation stats
            monthly_stats = {
                "total_months": monthly_demand["year_month"].nunique(),
                "total_products": monthly_demand["nama_barang"].nunique(),
                "avg_monthly_sales": monthly_demand["qty_sold"].mean(),
                "min_monthly_sales": monthly_demand["qty_sold"].min(),
                "max_monthly_sales": monthly_demand["qty_sold"].max(),
            }

            log_message("📊 Monthly Aggregation Summary:", "SUCCESS", 2)
            log_message(f"   • Records: {len(monthly_demand):,}", "INFO", 3)
            log_message(f"   • Months: {monthly_stats['total_months']}", "INFO", 3)
            log_message(f"   • Products: {monthly_stats['total_products']}", "INFO", 3)
            log_message(
                f"   • Avg monthly: {monthly_stats['avg_monthly_sales']:.1f} units",
                "INFO",
                3,
            )
            log_message(
                f"   • Range: {monthly_stats['min_monthly_sales']:.0f} - {monthly_stats['max_monthly_sales']:.0f}",
                "INFO",
                3,
            )

            # Create lag feature for monthly data
            monthly_demand["prev_month_total"] = monthly_demand.groupby("nama_barang")[
                "qty_sold"
            ].shift(1)

            # Select features
            final_data = monthly_demand[
                ["nama_barang", "year_month", "qty_sold", "prev_month_total"]
            ].copy()

            # Data quality check
            initial_rows = len(final_data)
            final_data = final_data.dropna()
            final_rows = len(final_data)
            removed_rows = initial_rows - final_rows

            log_message(f"🧹 Data cleaning results:", "INFO", 2)
            log_message(f"   • Initial rows: {initial_rows:,}", "INFO", 3)
            log_message(f"   • Removed (NaN): {removed_rows:,}", "INFO", 3)
            log_message(f"   • Final rows: {final_rows:,}", "INFO", 3)

        else:
            raise ValueError("prediction_type must be 'daily' or 'monthly'")

        # Validate final dataset
        if final_data.empty:
            raise ValueError("❌ No data remaining after feature engineering")

        # Product coverage analysis
        min_samples = CONFIG[f"MIN_SAMPLES_{prediction_type.upper()}"]
        product_counts = final_data["nama_barang"].value_counts()
        valid_products = product_counts[product_counts >= min_samples].index

        log_message(f"📋 Product coverage analysis:", "INFO", 1)
        log_message(f"   • Min samples required: {min_samples}", "INFO", 2)
        log_message(
            f"   • Products with sufficient data: {len(valid_products)}", "INFO", 2
        )
        log_message(
            f"   • Products filtered out: {len(product_counts) - len(valid_products)}",
            "INFO",
            2,
        )

        # Filter to valid products only
        final_data = final_data[final_data["nama_barang"].isin(valid_products)]

        # Final statistics
        target_stats = final_data["qty_sold"].describe()
        log_message("🎯 Target Variable Statistics:", "SUCCESS", 1)
        log_message(f"   • Count: {target_stats['count']:,.0f}", "INFO", 2)
        log_message(f"   • Mean: {target_stats['mean']:.2f}", "INFO", 2)
        log_message(f"   • Std: {target_stats['std']:.2f}", "INFO", 2)
        log_message(
            f"   • Range: {target_stats['min']:.0f} - {target_stats['max']:.0f}",
            "INFO",
            2,
        )
        log_message(
            f"   • Quartiles: {target_stats['25%']:.1f} | {target_stats['50%']:.1f} | {target_stats['75%']:.1f}",
            "INFO",
            2,
        )

        log_message(f"✅ Feature engineering completed successfully", "SUCCESS")
        log_message(
            f"📊 Final dataset ready: {len(final_data):,} records for {len(valid_products)} products",
            "SUCCESS",
            1,
        )

        return final_data

    except Exception as e:
        log_message(f"💥 Error in feature engineering: {e}", "ERROR")
        raise


def train_model(data, prediction_type="daily", model_output_path=None):
    """Train Random Forest model for daily or monthly prediction

    Args:
        data: Processed data with features
        prediction_type: 'daily' or 'monthly'
        model_output_path: Path to save the model

    Returns:
        Model performance metrics and info
    """
    try:
        if model_output_path is None:
            model_output_path = f"model/rf_stock_predictor_{prediction_type}.pkl"

        log_message(
            f"Memulai training model untuk prediksi {prediction_type.upper()}..."
        )

        # Define features based on prediction type
        if prediction_type == "daily":
            feature_cols = [
                "nama_barang",
                "lag1",  # Penjualan kemarin
                "lag2",  # Penjualan 2 hari lalu
                "lag3",  # Penjualan 3 hari lalu
            ]
            log_message("🗓️  PREDIKSI HARIAN")
            log_message("📊 Input Features:")
            log_message("   - lag1: Penjualan kemarin")
            log_message("   - lag2: Penjualan 2 hari lalu")
            log_message("   - lag3: Penjualan 3 hari lalu")
            log_message("🎯 Output: Total penjualan hari ini")

        elif prediction_type == "monthly":
            feature_cols = [
                "nama_barang",
                "prev_month_total",  # Total penjualan bulan lalu
            ]
            log_message("📅 PREDIKSI BULANAN")
            log_message("📊 Input Features:")
            log_message("   - prev_month_total: Total penjualan bulan sebelumnya")
            log_message("🎯 Output: Total penjualan bulan ini")

        else:
            raise ValueError("prediction_type harus 'daily' atau 'monthly'")

        target_col = "qty_sold"

        # Validate required columns
        missing_cols = [
            col for col in feature_cols + [target_col] if col not in data.columns
        ]
        if missing_cols:
            raise ValueError(f"Kolom yang diperlukan tidak ditemukan: {missing_cols}")

        X = data[feature_cols]
        y = data[target_col]

        log_message(f"Training data: {len(X)} samples, {len(X.columns)} features")
        log_message(f"Unique products: {X['nama_barang'].nunique()}")
        log_message(
            f"Target statistics - Mean: {y.mean():.2f}, Std: {y.std():.2f}, Min: {y.min()}, Max: {y.max()}"
        )

        # Create preprocessing pipeline
        log_message("Membuat preprocessing pipeline...")
        ct = ColumnTransformer(
            [("cat", OneHotEncoder(handle_unknown="ignore"), ["nama_barang"])],
            remainder="passthrough",
        )

        # Create model with adjusted parameters based on prediction type
        if prediction_type == "daily":
            # Daily prediction: more trees, moderate depth
            model = RandomForestRegressor(
                n_estimators=300,
                random_state=42,
                max_depth=10,
                min_samples_split=5,
                min_samples_leaf=2,
                n_jobs=-1,
            )
        else:
            # Monthly prediction: fewer trees, shallower depth
            model = RandomForestRegressor(
                n_estimators=200,
                random_state=42,
                max_depth=8,
                min_samples_split=3,
                min_samples_leaf=1,
                n_jobs=-1,
            )

        # Create pipeline
        pipe = Pipeline([("prep", ct), ("model", model)])

        # Time series cross-validation
        log_message("Melakukan Time Series Cross Validation...")
        tscv = TimeSeriesSplit(n_splits=3)

        maes, r2s, rmses = [], [], []
        fold = 1

        for train_idx, test_idx in tscv.split(data):
            log_message(f"Training fold {fold}/3...")

            X_train, X_test = X.iloc[train_idx], X.iloc[test_idx]
            y_train, y_test = y.iloc[train_idx], y.iloc[test_idx]

            # Fit model
            pipe.fit(X_train, y_train)

            # Predict
            preds = pipe.predict(X_test)

            # Calculate metrics
            mae = mean_absolute_error(y_test, preds)
            r2 = r2_score(y_test, preds)
            rmse = np.sqrt(mean_squared_error(y_test, preds))

            maes.append(mae)
            r2s.append(r2)
            rmses.append(rmse)

            log_message(f"Fold {fold} - MAE: {mae:.2f}, R²: {r2:.3f}, RMSE: {rmse:.2f}")
            fold += 1

        # Final metrics
        avg_mae = np.mean(maes)
        avg_r2 = np.mean(r2s)
        avg_rmse = np.mean(rmses)

        log_message("=" * 50)
        log_message(f"HASIL CROSS VALIDATION - {prediction_type.upper()}:")
        log_message(f"Rata-rata MAE : {avg_mae:.2f} ± {np.std(maes):.2f}")
        log_message(f"Rata-rata R²  : {avg_r2:.3f} ± {np.std(r2s):.3f}")
        log_message(f"Rata-rata RMSE: {avg_rmse:.2f} ± {np.std(rmses):.2f}")
        log_message("=" * 50)

        # Train final model on all data
        log_message("Training model final pada semua data...")
        pipe.fit(X, y)

        # Feature importance (for Random Forest)
        try:
            feature_names = list(
                pipe.named_steps["prep"]
                .named_transformers_["cat"]
                .get_feature_names_out()
            ) + [col for col in feature_cols if col != "nama_barang"]
            importance = pipe.named_steps["model"].feature_importances_

            # Show feature importance
            feature_importance = list(zip(feature_names, importance))
            feature_importance.sort(key=lambda x: x[1], reverse=True)

            log_message(f"FEATURE IMPORTANCE - {prediction_type.upper()}:")
            for i, (feature, imp) in enumerate(feature_importance[:10], 1):
                log_message(f"{i:2d}. {feature:<30} : {imp:.4f}")

        except Exception as e:
            log_message(
                f"Warning: Could not extract feature importance: {e}", "WARNING"
            )

        # Prepare model metadata
        model_metadata = {
            "model_version": CONFIG["MODEL_VERSION"],
            "training_date": CONFIG["TRAINING_DATE"],
            "prediction_type": prediction_type,
            "model_type": "RandomForest",
            "n_estimators": pipe.named_steps["model"].n_estimators,
            "max_depth": pipe.named_steps["model"].max_depth,
            "performance_metrics": {
                "cross_validation": {
                    "mae_mean": avg_mae,
                    "mae_std": np.std(maes),
                    "r2_mean": avg_r2,
                    "r2_std": np.std(r2s),
                    "rmse_mean": avg_rmse,
                    "rmse_std": np.std(rmses),
                    "cv_splits": CONFIG["CV_SPLITS"],
                }
            },
            "training_data": {
                "n_samples": len(X),
                "n_features": len(X.columns),
                "n_products": X["nama_barang"].nunique(),
                "target_stats": {
                    "mean": float(y.mean()),
                    "std": float(y.std()),
                    "min": float(y.min()),
                    "max": float(y.max()),
                    "median": float(y.median()),
                    "q25": float(y.quantile(0.25)),
                    "q75": float(y.quantile(0.75)),
                },
            },
            "feature_columns": feature_cols,
            "target_column": target_col,
        }

        # Add feature importance to metadata if available
        try:
            feature_names = list(
                pipe.named_steps["prep"]
                .named_transformers_["cat"]
                .get_feature_names_out()
            ) + [col for col in feature_cols if col != "nama_barang"]
            importance = pipe.named_steps["model"].feature_importances_

            model_metadata["feature_importance"] = [
                {"feature": name, "importance": float(imp)}
                for name, imp in zip(feature_names, importance)
            ]

            # Sort by importance for easier reading
            model_metadata["feature_importance"].sort(
                key=lambda x: x["importance"], reverse=True
            )

        except Exception as e:
            log_message(f"⚠️ Could not extract feature importance: {e}", "WARNING", 2)

        # Save model
        log_message(f"💾 Saving model to {model_output_path}...", "INFO", 1)

        # Create model directory if it doesn't exist
        model_dir = os.path.dirname(model_output_path)
        if model_dir and not os.path.exists(model_dir):
            os.makedirs(model_dir)

        # Save model and metadata
        joblib.dump(pipe, model_output_path)

        # Save metadata
        metadata_path = model_output_path.replace(".pkl", "_metadata.json")
        with open(metadata_path, "w", encoding="utf-8") as f:
            json.dump(model_metadata, f, indent=2, ensure_ascii=False)

        log_message(f"✅ Model saved successfully!", "SUCCESS", 1)
        log_message(f"   📦 Model file: {model_output_path}", "INFO", 2)
        log_message(f"   📋 Metadata: {metadata_path}", "INFO", 2)

        # Model health check and validation
        log_message("🔍 Performing model health checks...", "INFO", 1)

        # 1. Prediction sanity check
        try:
            # Test with a few samples
            test_samples = X.head(min(5, len(X)))
            test_predictions = pipe.predict(test_samples)

            # Ensure predictions is numpy array
            test_predictions = np.array(test_predictions)

            # Check for reasonable predictions
            if np.any(np.isnan(test_predictions)) or np.any(np.isinf(test_predictions)):
                log_message("❌ Model produces NaN or infinite predictions", "ERROR", 2)
            elif np.all(test_predictions <= 0):
                log_message("⚠️ All test predictions are zero or negative", "WARNING", 2)
            elif np.std(test_predictions) == 0:
                log_message("⚠️ Model predictions show no variance", "WARNING", 2)
            else:
                log_message(f"✅ Health check passed", "SUCCESS", 2)
                log_message(
                    f"   Test predictions range: {test_predictions.min():.2f} - {test_predictions.max():.2f}",
                    "INFO",
                    3,
                )

        except Exception as e:
            log_message(f"⚠️ Health check failed: {e}", "WARNING", 2)

        # 2. Feature importance validation
        try:
            if "feature_importance" in model_metadata:
                top_features = model_metadata["feature_importance"][:3]
                log_message("🏆 Top 3 most important features:", "INFO", 2)
                for i, feat in enumerate(top_features, 1):
                    log_message(
                        f"   {i}. {feat['feature']}: {feat['importance']:.4f}",
                        "INFO",
                        3,
                    )
        except Exception as e:
            log_message(f"⚠️ Feature importance analysis failed: {e}", "WARNING", 2)

        # 3. Performance validation
        performance_warning = False
        if avg_r2 < 0.1:
            log_message(
                "⚠️ Low R² score - model may have poor predictive power", "WARNING", 2
            )
            performance_warning = True
        if avg_mae > y.std():
            log_message("⚠️ High MAE relative to target variance", "WARNING", 2)
            performance_warning = True

        if not performance_warning:
            log_message("✅ Performance metrics within acceptable ranges", "SUCCESS", 2)

        # 4. File validation
        if os.path.exists(model_output_path):
            file_size = os.path.getsize(model_output_path) / (1024 * 1024)  # MB
            log_message(f"📁 Model file size: {file_size:.2f} MB", "INFO", 2)

        return {
            "mae": avg_mae,
            "r2": avg_r2,
            "rmse": avg_rmse,
            "mae_std": np.std(maes),
            "r2_std": np.std(r2s),
            "rmse_std": np.std(rmses),
            "n_samples": len(X),
            "n_products": X["nama_barang"].nunique(),
            "model_path": model_output_path,
            "metadata_path": metadata_path,
            "feature_importance": model_metadata.get("feature_importance", []),
            "target_stats": model_metadata["training_data"]["target_stats"],
            "health_check_passed": not performance_warning,
            "prediction_type": prediction_type,
        }

    except Exception as e:
        log_message(f"Error dalam training model: {e}", "ERROR")
        raise


def main():
    """Main function with complete error handling"""
    try:
        log_message("🚀 MEMULAI TRAINING STOCK PREDICTION MODEL")
        log_message("=" * 60)

        # Load and process data
        df = load_and_process_data("scripts/data")
        sales = process_sales_data(df)

        # Train both daily and monthly models
        results = {}

        # 1. DAILY PREDICTION MODEL
        log_message("\n" + "=" * 60)
        log_message("🗓️  TRAINING MODEL PREDIKSI HARIAN")
        log_message("=" * 60)

        daily_data = create_features(sales, prediction_type="daily")
        daily_results = train_model(daily_data, prediction_type="daily")
        results["daily"] = daily_results

        # 2. MONTHLY PREDICTION MODEL
        log_message("\n" + "=" * 60)
        log_message("📅 TRAINING MODEL PREDIKSI BULANAN")
        log_message("=" * 60)

        monthly_data = create_features(sales, prediction_type="monthly")
        monthly_results = train_model(monthly_data, prediction_type="monthly")
        results["monthly"] = monthly_results

        # Success summary
        log_message("\n" + "=" * 60)
        log_message("✅ TRAINING BERHASIL DISELESAIKAN!")
        log_message("=" * 60)

        # Enhanced success summary with detailed reporting
        print_header("🎉 TRAINING COMPLETED SUCCESSFULLY!")

        log_message("📊 FINAL PERFORMANCE SUMMARY", "SUCCESS")
        log_message("=" * 50, "INFO")

        # Daily model summary
        daily_results = results["daily"]
        log_message("🗓️  DAILY PREDICTION MODEL", "INFO")
        log_message(f"   📈 Performance:", "INFO", 1)
        log_message(
            f"      • MAE: {daily_results['mae']:.2f} ± {daily_results.get('mae_std', 0):.2f}",
            "INFO",
            2,
        )
        log_message(
            f"      • R²:  {daily_results['r2']:.3f} ± {daily_results.get('r2_std', 0):.3f}",
            "INFO",
            2,
        )
        log_message(
            f"      • RMSE: {daily_results['rmse']:.2f} ± {daily_results.get('rmse_std', 0):.2f}",
            "INFO",
            2,
        )
        log_message(f"   📦 Training Data:", "INFO", 1)
        log_message(f"      • Samples: {daily_results['n_samples']:,}", "INFO", 2)
        log_message(f"      • Products: {daily_results['n_products']}", "INFO", 2)
        log_message(f"   💾 Files:", "INFO", 1)
        log_message(
            f"      • Model: {os.path.basename(daily_results['model_path'])}", "INFO", 2
        )
        log_message(
            f"      • Metadata: {os.path.basename(daily_results.get('metadata_path', ''))}",
            "INFO",
            2,
        )
        log_message(f"   🎯 Usage:", "INFO", 1)
        log_message(
            f"      • Input: 3 lag features (yesterday, 2 days ago, 3 days ago)",
            "INFO",
            2,
        )
        log_message(f"      • Output: Predicted daily sales quantity", "INFO", 2)

        # Health check status for daily model
        if daily_results.get("health_check_passed", True):
            log_message(f"   ✅ Health Check: PASSED", "SUCCESS", 1)
        else:
            log_message(f"   ⚠️ Health Check: WARNINGS (see above)", "WARNING", 1)

        print()  # Spacing

        # Monthly model summary
        monthly_results = results["monthly"]
        log_message("📅 MONTHLY PREDICTION MODEL", "INFO")
        log_message(f"   📈 Performance:", "INFO", 1)
        log_message(
            f"      • MAE: {monthly_results['mae']:.2f} ± {monthly_results.get('mae_std', 0):.2f}",
            "INFO",
            2,
        )
        log_message(
            f"      • R²:  {monthly_results['r2']:.3f} ± {monthly_results.get('r2_std', 0):.3f}",
            "INFO",
            2,
        )
        log_message(
            f"      • RMSE: {monthly_results['rmse']:.2f} ± {monthly_results.get('rmse_std', 0):.2f}",
            "INFO",
            2,
        )
        log_message(f"   📦 Training Data:", "INFO", 1)
        log_message(f"      • Samples: {monthly_results['n_samples']:,}", "INFO", 2)
        log_message(f"      • Products: {monthly_results['n_products']}", "INFO", 2)
        log_message(f"   💾 Files:", "INFO", 1)
        log_message(
            f"      • Model: {os.path.basename(monthly_results['model_path'])}",
            "INFO",
            2,
        )
        log_message(
            f"      • Metadata: {os.path.basename(monthly_results.get('metadata_path', ''))}",
            "INFO",
            2,
        )
        log_message(f"   🎯 Usage:", "INFO", 1)
        log_message(f"      • Input: Previous month's total sales", "INFO", 2)
        log_message(f"      • Output: Predicted monthly sales quantity", "INFO", 2)

        # Health check status for monthly model
        if monthly_results.get("health_check_passed", True):
            log_message(f"   ✅ Health Check: PASSED", "SUCCESS", 1)
        else:
            log_message(f"   ⚠️ Health Check: WARNINGS (see above)", "WARNING", 1)

        # Next steps and recommendations
        print()
        log_message("🚀 NEXT STEPS & RECOMMENDATIONS", "INFO")
        log_message("=" * 40, "INFO")
        log_message("1. Test predictions with:", "INFO", 1)
        log_message("   python scripts/predict.py", "INFO", 2)
        log_message("2. Analyze model performance:", "INFO", 1)
        log_message("   python scripts/analyze_model.py", "INFO", 2)
        log_message("3. For high-value predictions, consider:", "INFO", 1)
        log_message("   python scripts/predict_with_scaling.py", "INFO", 2)
        log_message("4. Check training data coverage:", "INFO", 1)
        log_message("   python scripts/analyze_data.py", "INFO", 2)

        # Performance interpretation guide
        log_message("\n📖 PERFORMANCE INTERPRETATION GUIDE", "INFO")
        log_message("=" * 40, "INFO")
        log_message(
            "• MAE (Mean Absolute Error): Average prediction error in units", "INFO", 1
        )
        log_message(
            "• R² (R-squared): Variance explained (closer to 1.0 is better)", "INFO", 1
        )
        log_message(
            "• RMSE (Root Mean Square Error): Penalizes larger errors more", "INFO", 1
        )
        log_message(
            "• For business use: Focus on MAE as it's most interpretable", "INFO", 1
        )

        print_header("🎯 TRAINING PROCESS COMPLETED", char="=")

        return results

    except Exception as e:
        log_message(f"❌ TRAINING GAGAL: {e}", "ERROR")
        sys.exit(1)


if __name__ == "__main__":
    main()
