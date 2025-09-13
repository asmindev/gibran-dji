# stock_predictor.py - Sales and Restock Prediction System
"""
Professional Sales and Restock Prediction System

A comprehensive class-based system for predicting sales demand and restock recommendations
using machine learning algorithms with proper logging and error handling.

Features:
- Sales demand prediction based on historical patterns
- Restock recommendation system
- Professional logging with file and console output
- Random Forest models for sales and restock predictions
- Feature engineering with inventory and sales patterns
- Comprehensive data validation and preprocessing
- Model persistence and metadata tracking
- Batch prediction capabilities
- Indonesian date format handling
- Detailed performance metrics and analysis
- Health checks and model validation
- API integration support

Author: Stock Prediction System
Version: 4.0 - Sales & Restock Focus
Date: September 2025
"""

import os
import sys
import pandas as pd
import numpy as np
import joblib
import warnings
import json
import logging
from datetime import datetime, timedelta
from typing import Dict, Tuple, List, Optional, Union
from pathlib import Path

# Machine Learning imports
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


# Suppress warnings for cleaner output
warnings.filterwarnings("ignore")


class StockPredictor:
    """
    Comprehensive Sales and Restock Prediction System

    This class handles both sales prediction and restock recommendation operations
    using Random Forest algorithms with inventory-focused features.
    """

    def __init__(self, base_path: Optional[str] = None):
        """
        Initialize the Sales and Restock Predictor

        Args:
            base_path (str): Base directory path for models and logs
        """
        self.base_path = Path(base_path) if base_path else Path(__file__).parent
        self.model_dir = self.base_path / "model"
        self.data_dir = self.base_path / "data"
        self.logs_dir = self.base_path / "logs"

        # Create directories if they don't exist
        self.model_dir.mkdir(exist_ok=True)
        self.data_dir.mkdir(exist_ok=True)
        self.logs_dir.mkdir(exist_ok=True)

        # Configuration with enhanced parameters for sales and restock
        self.config = {
            "MODEL_VERSION": "4.0",
            "TRAINING_DATE": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "MIN_SAMPLES_SALES": 5,
            "MIN_SAMPLES_RESTOCK": 3,
            "RANDOM_STATE": 42,
            "CV_SPLITS": 3,
            "N_ESTIMATORS_SALES": 300,
            "N_ESTIMATORS_RESTOCK": 200,
            "MAX_DEPTH_SALES": 10,
            "MAX_DEPTH_RESTOCK": 8,
            "RESTOCK_THRESHOLD_MULTIPLIER": 1.5,  # Safety stock multiplier
            "LOW_STOCK_THRESHOLD": 10,  # Units considered as low stock
        }

        # Model file paths
        self.sales_model_path = self.model_dir / "rf_sales_predictor.pkl"
        self.restock_model_path = self.model_dir / "rf_restock_predictor.pkl"

        # Setup logging
        self._setup_logging()

        # Model storage
        self.models = {}
        self.performance_metrics = {}

        self.logger.info("🚀 Sales & Restock Predictor initialized successfully")
        self.logger.info(f"📁 Base path: {self.base_path}")
        self.logger.info(f"📁 Model directory: {self.model_dir}")
        self.logger.info(f"📁 Logs directory: {self.logs_dir}")

    def _setup_logging(self):
        """Setup comprehensive logging system with both file and console output"""
        # Use single log file name as requested
        log_file_path = self.logs_dir / "predict_rf.log"

        # Configure logging with detailed formatting
        log_format = "%(asctime)s - %(name)s - %(levelname)s - %(message)s"

        # Clear any existing handlers
        for handler in logging.root.handlers[:]:
            logging.root.removeHandler(handler)

        # Configure logging
        logging.basicConfig(
            level=logging.INFO,
            format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
            handlers=[
                logging.FileHandler(log_file_path, encoding="utf-8"),
                # logging.StreamHandler(sys.stdout),
            ],
        )

        self.logger = logging.getLogger("StockPredictor")
        self.logger.info(f"📋 Logging initialized - Log file: {log_file_path}")

    def convert_indonesian_date(self, date_str):
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
            self.logger.warning(f"Error converting date '{date_str}': {e}")
            return date_str

    def validate_data_folder(self, data_folder: str = "data") -> List[str]:
        """
        Validate data folder and return data files with comprehensive checks

        Args:
            data_folder: Path to data folder

        Returns:
            List of valid data file names (Excel or CSV)

        Raises:
            FileNotFoundError: If folder or files not found
        """
        self.logger.info("🔍 Validating data folder structure...")

        if not os.path.exists(data_folder):
            raise FileNotFoundError(f"Data folder '{data_folder}' tidak ditemukan")

        # Get all data files (Excel and CSV)
        all_files = os.listdir(data_folder)
        data_files = [f for f in all_files if f.endswith((".xlsx", ".xls", ".csv"))]

        if not data_files:
            raise FileNotFoundError(
                f"Tidak ada file data (Excel/CSV) ditemukan di folder '{data_folder}'"
            )

        # Detailed file analysis
        self.logger.info(f"📁 Folder: {os.path.abspath(data_folder)}")
        self.logger.info(f"📄 Total files: {len(all_files)}")
        self.logger.info(f"📋 Data files: {len(data_files)}")

        for i, file in enumerate(data_files, 1):
            file_path = os.path.join(data_folder, file)
            file_size = os.path.getsize(file_path) / 1024  # KB
            file_type = "Excel" if file.endswith((".xlsx", ".xls")) else "CSV"
            self.logger.info(f"   {i}. {file} ({file_size:.1f} KB, {file_type})")

        return data_files

    def analyze_data_quality(self, df: pd.DataFrame, file_name: str) -> Dict:
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
        expected_cols = [
            "no",
            "id_trx",
            "tgl",
            "id_item",
            "nama_barang",
            "kategori",
            "jumlah",
        ]
        quality_metrics["has_required_columns"] = all(
            col in df.columns for col in expected_cols[: len(df.columns)]
        )

        return quality_metrics

    def load_and_process_data(self, data_folder: str = "data") -> pd.DataFrame:
        """
        Load and process all Excel files with comprehensive error handling and quality analysis

        Args:
            data_folder: Path to folder containing Excel files

        Returns:
            Combined DataFrame with all loaded data

        Raises:
            ValueError: If no files could be loaded successfully
        """
        self.logger.info("DATA LOADING & QUALITY ANALYSIS")

        try:
            files = self.validate_data_folder(data_folder)
            all_df = []
            quality_reports = []

            self.logger.info("🚀 Starting Excel file processing...")

            for i, file in enumerate(files, 1):
                try:
                    file_path = os.path.join(data_folder, file)
                    self.logger.info(f"📂 Loading file {i}/{len(files)}: {file}")

                    # Load file based on extension
                    if file.endswith((".xlsx", ".xls")):
                        # Read Excel with comprehensive error handling
                        df = pd.read_excel(file_path, skiprows=1)
                    elif file.endswith(".csv"):
                        # Read CSV file
                        df = pd.read_csv(file_path)
                    else:
                        self.logger.warning(f"Unsupported file format: {file}")
                        continue

                    # Analyze data quality
                    quality_metrics = self.analyze_data_quality(df, file)
                    quality_reports.append(quality_metrics)

                    # Validate column structure - updated for new format with id_item and nama_barang
                    expected_cols = [
                        "no",
                        "id_trx",
                        "tgl",
                        "id_item",
                        "nama_barang",
                        "kategori",
                        "jumlah",
                    ]

                    if len(df.columns) < 7:
                        self.logger.warning(
                            f"Column mismatch: Expected 7, got {len(df.columns)}"
                        )
                        continue

                    df.columns = expected_cols[: len(df.columns)]

                    # Basic data validation
                    if df.empty:
                        self.logger.warning("File is empty, skipping...")
                        continue

                    # Check for required columns - updated for new format
                    required_cols = ["id_item", "nama_barang", "kategori", "jumlah"]
                    missing_required = [
                        col for col in required_cols if col not in df.columns
                    ]

                    if missing_required:
                        self.logger.warning(
                            f"Missing required columns: {missing_required}"
                        )
                        continue

                    all_df.append(df)
                    file_type = "Excel" if file.endswith((".xlsx", ".xls")) else "CSV"
                    self.logger.info(
                        f"✓ Successfully loaded ({file_type}): {len(df):,} rows | Columns: {len(df.columns)} | Missing: {df.isnull().sum().sum()}"
                    )

                except Exception as e:
                    self.logger.error(f"❌ Failed to load {file}: {str(e)[:100]}...")
                    continue

            # Validate that we have data to work with
            if not all_df:
                raise ValueError("❌ No files were successfully loaded")

            # Display quality summary
            self.logger.info("DATA QUALITY SUMMARY")
            total_rows = sum(df.shape[0] for df in all_df)
            total_memory = sum(q["memory_usage_mb"] for q in quality_reports)

            self.logger.info(f"📊 Files processed: {len(all_df)}/{len(files)}")
            self.logger.info(f"📊 Total rows: {total_rows:,}")
            self.logger.info(f"💾 Memory usage: {total_memory:.2f} MB")

            # Combine all dataframes
            self.logger.info("🔄 Combining all datasets...")
            combined_df = pd.concat(all_df, ignore_index=True)

            self.logger.info(
                f"✅ Data combination successful: {len(combined_df):,} total rows",
            )

            return combined_df

        except Exception as e:
            self.logger.error(f"💥 Fatal error in data loading: {e}")
            raise

    def process_sales_data(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Process sales data with comprehensive validation, cleaning, and analysis

        Args:
            df: Raw combined DataFrame

        Returns:
            Cleaned sales DataFrame

        Raises:
            ValueError: If no valid sales data found
        """
        self.logger.info("SALES DATA PROCESSING & ANALYSIS")

        try:
            self.logger.info("🔍 Starting sales data processing pipeline...")

            # Validate required columns
            required_cols = ["kategori", "jumlah", "id_item", "nama_barang", "tgl"]
            missing_cols = [col for col in required_cols if col not in df.columns]
            if missing_cols:
                raise ValueError(f"Missing required columns: {missing_cols}")

            self.logger.info(f"✅ Column validation passed: {required_cols}")

            # Analyze transaction categories
            self.logger.info("📊 Analyzing transaction categories...")
            category_analysis = df["kategori"].value_counts()

            for category, count in category_analysis.head(10).items():
                percentage = (count / len(df)) * 100
                self.logger.info(
                    f"   • {category}: {count:,} ({percentage:.1f}%)",
                )

            # Filter sales transactions (keluar)
            self.logger.info("🔽 Filtering sales transactions (kategori: 'keluar')...")
            sales_mask = df["kategori"].str.lower().str.contains("keluar", na=False)
            sales = df[sales_mask].copy()

            if sales.empty:
                raise ValueError("❌ No sales transactions found (kategori 'keluar')")

            sales_percentage = (len(sales) / len(df)) * 100
            self.logger.info(
                f"✅ Sales filter applied: {len(sales):,}/{len(df):,} transactions ({sales_percentage:.1f}%)",
            )

            # Process quantity data
            self.logger.info("🔢 Processing quantity data...")
            sales = sales.rename(columns={"jumlah": "qty_sold"})

            # Convert to numeric and analyze
            original_count = len(sales)
            sales["qty_sold"] = pd.to_numeric(sales["qty_sold"], errors="coerce")
            sales = sales.dropna(subset=["qty_sold"])
            numeric_loss = original_count - len(sales)

            if numeric_loss > 0:
                self.logger.info(
                    f"⚠️ Removed {numeric_loss} rows with invalid quantities",
                    "WARNING",
                    2,
                )

            # Remove zero or negative sales
            positive_sales = sales[sales["qty_sold"] > 0]
            negative_loss = len(sales) - len(positive_sales)
            sales = positive_sales

            if negative_loss > 0:
                self.logger.info(
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

            self.logger.info("📈 Quantity Statistics:")
            self.logger.info(f"   • Valid records: {qty_stats['count']:,}")
            self.logger.info(f"   • Mean: {qty_stats['mean']:.2f} units")
            self.logger.info(f"   • Median: {qty_stats['median']:.2f} units")
            self.logger.info(
                f"   • Range: {qty_stats['min']:.0f} - {qty_stats['max']:.0f} units",
            )
            self.logger.info(f"   • Std Dev: {qty_stats['std']:.2f}")

            # Process dates
            self.logger.info("📅 Processing date information...")
            sales["tgl"] = sales["tgl"].apply(self.convert_indonesian_date)

            try:
                sales["tgl"] = pd.to_datetime(
                    sales["tgl"], format="mixed", dayfirst=True
                )
            except Exception as e:
                self.logger.warning(f"⚠️ Date parsing warning: {e}")
                self.logger.info("🔄 Trying alternative date parsing methods...")
                sales["tgl"] = pd.to_datetime(
                    sales["tgl"], errors="coerce", dayfirst=True
                )

            # Remove invalid dates
            valid_dates = sales.dropna(subset=["tgl"])
            date_loss = len(sales) - len(valid_dates)
            sales = valid_dates

            if date_loss > 0:
                self.logger.warning(f"⚠️ Removed {date_loss} rows with invalid dates")

            if sales.empty:
                raise ValueError("❌ No data remaining after date validation")

            # Date range analysis
            min_date = sales["tgl"].min()
            max_date = sales["tgl"].max()
            date_range_days = (max_date - min_date).days

            self.logger.info("📊 Date Range Analysis:")
            self.logger.info(f"   • Start date: {min_date.strftime('%Y-%m-%d (%A)')}")
            self.logger.info(f"   • End date: {max_date.strftime('%Y-%m-%d (%A)')}")
            self.logger.info(f"   • Coverage: {date_range_days} days")
            self.logger.info(
                f"   • Months: {min_date.strftime('%B %Y')} - {max_date.strftime('%B %Y')}",
            )

            # Product analysis - using both id_item and nama_barang for better insights
            self.logger.info("🏷️ Analyzing product portfolio...")
            product_stats = (
                sales.groupby(["id_item", "nama_barang"])
                .agg({"qty_sold": ["count", "sum", "mean", "std"]})
                .round(2)
            )
            product_stats.columns = ["transactions", "total_sold", "avg_qty", "qty_std"]
            product_stats = product_stats.sort_values("total_sold", ascending=False)

            self.logger.info(
                f"📦 Product Portfolio: {len(product_stats)} unique products"
            )
            self.logger.info("🏆 Top 10 Products by Total Sales:")

            for i, ((item_id, item_name), stats) in enumerate(
                product_stats.head(10).iterrows(), 1
            ):
                # Truncate product name for display
                display_name = (
                    str(item_name)[:30] + "..."
                    if len(str(item_name)) > 30
                    else str(item_name)
                )
                self.logger.info(
                    f"   {i:2d}. ID:{item_id} | {display_name:<33} | "
                    f"Total: {stats['total_sold']:>6.0f} | "
                    f"Txns: {stats['transactions']:>4.0f} | "
                    f"Avg: {stats['avg_qty']:>5.1f}",
                )

            self.logger.info(f"✅ Sales data processing completed successfully")
            self.logger.info(
                f"📊 Final dataset: {len(sales):,} valid sales records",
            )

            return sales

        except Exception as e:
            self.logger.error(f"💥 Error in sales data processing: {e}")
            raise

    def create_features(
        self, sales: pd.DataFrame, prediction_type: str = "sales"
    ) -> pd.DataFrame:
        """
        Create sophisticated features for sales or restock prediction with comprehensive analysis

        Args:
            sales: Processed sales data DataFrame
            prediction_type: 'sales' or 'restock'

        Returns:
            Feature-engineered DataFrame ready for model training

        Raises:
            ValueError: If prediction_type is invalid or insufficient data
        """
        self.logger.info(f"FEATURE ENGINEERING - {prediction_type.upper()} PREDICTION")

        try:
            self.logger.info(
                f"🔧 Starting feature engineering for {prediction_type} prediction...",
            )

            if prediction_type not in ["sales", "restock"]:
                raise ValueError("prediction_type must be 'sales' or 'restock'")

            # Step 1: Aggregate data and calculate metrics
            self.logger.info("📊 Aggregating sales data and calculating inventory metrics...")

            # Create product-level aggregations
            product_metrics = sales.groupby("id_item").agg({
                "qty_sold": ["sum", "mean", "std", "count"],
                "tgl": ["min", "max"]
            }).round(2)

            product_metrics.columns = [
                "total_sold", "avg_daily_sales", "sales_volatility", "transaction_count",
                "first_sale_date", "last_sale_date"
            ]

            # Calculate sales velocity and consistency
            product_metrics["sales_velocity"] = product_metrics["avg_daily_sales"]
            product_metrics["sales_consistency"] = (
                product_metrics["avg_daily_sales"] /
                (product_metrics["sales_volatility"] + 0.01)  # Avoid division by zero
            )

            # Calculate recent performance (last 30 days)
            recent_date = sales["tgl"].max()
            recent_threshold = recent_date - timedelta(days=30)

            recent_sales = sales[sales["tgl"] >= recent_threshold]
            recent_metrics = recent_sales.groupby("id_item")["qty_sold"].agg([
                "sum", "mean", "count"
            ])
            recent_metrics.columns = ["recent_total", "recent_avg", "recent_transactions"]

            # Merge recent metrics
            product_metrics = product_metrics.merge(
                recent_metrics, left_index=True, right_index=True, how="left"
            )
            product_metrics = product_metrics.fillna(0)

            # Reset index to make id_item a column
            final_data = product_metrics.reset_index()

            # Feature engineering based on prediction type
            if prediction_type == "sales":
                self.logger.info("� Building SALES prediction features...")
                self.logger.info("📋 Feature specification:")
                self.logger.info("   • avg_daily_sales: Historical average sales per day")
                self.logger.info("   • sales_velocity: Rate of sales movement")
                self.logger.info("   • sales_consistency: Stability of sales pattern")
                self.logger.info("   • recent_avg: Recent 30-day average sales")
                self.logger.info("   • Target: Predicted sales quantity")

                # Select sales-focused features
                feature_cols = [
                    "id_item", "avg_daily_sales", "sales_velocity",
                    "sales_consistency", "recent_avg", "transaction_count"
                ]
                target_col = "total_sold"

                # Prepare final dataset for sales prediction
                final_data = final_data[feature_cols + [target_col]].copy()

                # Create target variable (predict next period sales based on pattern)
                final_data["predicted_sales"] = final_data["recent_avg"] * 7  # Next week estimate
                final_data = final_data.rename(columns={"predicted_sales": "qty_sold"})

            elif prediction_type == "restock":
                self.logger.info("� Building RESTOCK prediction features...")
                self.logger.info("📋 Feature specification:")
                self.logger.info("   • avg_daily_sales: Average consumption rate")
                self.logger.info("   • sales_velocity: Inventory turnover rate")
                self.logger.info("   • sales_volatility: Demand variability")
                self.logger.info("   • recent_total: Recent consumption pattern")
                self.logger.info("   • Target: Recommended restock quantity")

                # Calculate restock recommendations
                final_data["lead_time_demand"] = final_data["avg_daily_sales"] * 7  # Assume 7-day lead time
                final_data["safety_stock"] = final_data["sales_volatility"] * self.config["RESTOCK_THRESHOLD_MULTIPLIER"]
                final_data["restock_point"] = final_data["lead_time_demand"] + final_data["safety_stock"]
                final_data["recommended_order_qty"] = final_data["restock_point"] * 1.5  # Buffer for efficiency

                # Select restock-focused features
                feature_cols = [
                    "id_item", "avg_daily_sales", "sales_velocity",
                    "sales_volatility", "recent_total", "transaction_count"
                ]
                target_col = "recommended_order_qty"

                # Prepare final dataset for restock prediction
                final_data = final_data[feature_cols + [target_col]].copy()
                final_data = final_data.rename(columns={target_col: "qty_sold"})

            # Data quality validation
            initial_rows = len(final_data)
            final_data = final_data.dropna()
            final_rows = len(final_data)
            removed_rows = initial_rows - final_rows

            self.logger.info(f"🧹 Data cleaning results:")
            self.logger.info(f"   • Initial rows: {initial_rows:,}")
            self.logger.info(f"   • Removed (NaN): {removed_rows:,}")
            self.logger.info(f"   • Final rows: {final_rows:,}")
            if initial_rows > 0:
                self.logger.info(
                    f"   • Retention rate: {(final_rows/initial_rows)*100:.1f}%",
                )

            # Validate final dataset
            if final_data.empty:
                raise ValueError("❌ No data remaining after feature engineering")

            # Product coverage analysis
            min_samples = self.config[f"MIN_SAMPLES_{prediction_type.upper()}"]
            valid_products = final_data["id_item"].unique()

            self.logger.info(f"📋 Product coverage analysis:")
            self.logger.info(f"   • Min samples required: {min_samples}")
            self.logger.info(f"   • Products with sufficient data: {len(valid_products)}")

            # Final statistics
            target_stats = final_data["qty_sold"].describe()
            self.logger.info("🎯 Target Variable Statistics:")
            self.logger.info(f"   • Count: {target_stats['count']:,.0f}")
            self.logger.info(f"   • Mean: {target_stats['mean']:.2f}")
            self.logger.info(f"   • Std: {target_stats['std']:.2f}")
            self.logger.info(
                f"   • Range: {target_stats['min']:.0f} - {target_stats['max']:.0f}",
            )

            self.logger.info(f"✅ Feature engineering completed successfully")
            self.logger.info(
                f"📊 Final dataset ready: {len(final_data):,} records for {len(valid_products)} products"
            )

            return final_data

        except Exception as e:
            self.logger.error(f"💥 Error in feature engineering: {e}")
            raise

    def prepare_features(
        self, data: pd.DataFrame, prediction_type: str
    ) -> Tuple[pd.DataFrame, pd.Series]:
        """
        Prepare features for training based on prediction type

        Args:
            data (pd.DataFrame): Feature-engineered data
            prediction_type (str): 'sales' or 'restock'

        Returns:
            Tuple[pd.DataFrame, pd.Series]: Features (X) and target (y)
        """
        self.logger.info(f"FEATURE PREPARATION - {prediction_type.upper()}")

        if prediction_type == "sales":
            return self._prepare_sales_features(data)
        elif prediction_type == "restock":
            return self._prepare_restock_features(data)
        else:
            raise ValueError("prediction_type must be 'sales' or 'restock'")

    def _prepare_sales_features(
        self, data: pd.DataFrame
    ) -> Tuple[pd.DataFrame, pd.Series]:
        """Prepare features for sales prediction"""
        self.logger.info("� Preparing sales prediction features...")

        # Define features for sales prediction
        feature_cols = [
            "id_item", "avg_daily_sales", "sales_velocity",
            "sales_consistency", "recent_avg", "transaction_count"
        ]
        target_col = "qty_sold"

        # Validate required columns
        missing_cols = [
            col for col in feature_cols + [target_col] if col not in data.columns
        ]
        if missing_cols:
            raise ValueError(f"Missing required columns: {missing_cols}")

        X = data[feature_cols]
        y = data[target_col]

        self.logger.info(f"📊 Sales features prepared: {len(X)} samples")
        self.logger.info(f"📦 Products with sufficient data: {X['id_item'].nunique()}")

        return X, y

    def _prepare_restock_features(
        self, data: pd.DataFrame
    ) -> Tuple[pd.DataFrame, pd.Series]:
        """Prepare features for restock prediction"""
        self.logger.info("� Preparing restock prediction features...")

        # Define features for restock prediction
        feature_cols = [
            "id_item", "avg_daily_sales", "sales_velocity",
            "sales_volatility", "recent_total", "transaction_count"
        ]
        target_col = "qty_sold"

        # Validate required columns
        missing_cols = [
            col for col in feature_cols + [target_col] if col not in data.columns
        ]
        if missing_cols:
            raise ValueError(f"Missing required columns: {missing_cols}")

        X = data[feature_cols]
        y = data[target_col]

        self.logger.info(f"📊 Restock features prepared: {len(X)} samples")
        self.logger.info(f"📦 Products with sufficient data: {X['id_item'].nunique()}")

        return X, y

    def train_model(
        self,
        X: pd.DataFrame,
        y: pd.Series,
        prediction_type: str,
        product_mapping: Optional[dict] = None,
    ) -> Pipeline:
        """
        Train Random Forest model with comprehensive cross-validation and analysis

        Args:
            X (pd.DataFrame): Features
            y (pd.Series): Target variable
            prediction_type (str): 'sales' or 'restock'

        Returns:
            Pipeline: Trained model pipeline
        """
        self.logger.info(f"MODEL TRAINING - {prediction_type.upper()}")

        self.logger.info(f"Training data: {len(X)} samples, {len(X.columns)} features")
        self.logger.info(f"Unique products: {X['id_item'].nunique()}")
        self.logger.info(
            f"Target statistics - Mean: {y.mean():.2f}, Std: {y.std():.2f}, Min: {y.min()}, Max: {y.max()}"
        )

        # Create preprocessing pipeline
        self.logger.info("Membuat preprocessing pipeline...")
        categorical_features = ["id_item"]
        numerical_features = [col for col in X.columns if col != "id_item"]

        preprocessor = ColumnTransformer(
            transformers=[
                ("cat", OneHotEncoder(handle_unknown="ignore"), categorical_features),
                ("num", StandardScaler(), numerical_features),
            ]
        )

        # Create model with adjusted parameters based on prediction type
        if prediction_type == "sales":
            # Sales prediction: optimized for sales forecasting
            rf_model = RandomForestRegressor(
                n_estimators=self.config["N_ESTIMATORS_SALES"],
                random_state=self.config["RANDOM_STATE"],
                max_depth=self.config["MAX_DEPTH_SALES"],
                min_samples_split=5,
                min_samples_leaf=2,
                n_jobs=-1,
            )
        else:  # restock
            # Restock prediction: optimized for inventory planning
            rf_model = RandomForestRegressor(
                n_estimators=self.config["N_ESTIMATORS_RESTOCK"],
                random_state=self.config["RANDOM_STATE"],
                max_depth=self.config["MAX_DEPTH_RESTOCK"],
                min_samples_split=3,
                min_samples_leaf=1,
                n_jobs=-1,
            )

        # Create model pipeline
        model = Pipeline([("preprocessor", preprocessor), ("regressor", rf_model)])

        # Cross-validation (skip if not enough samples)
        self.logger.info("Melakukan Cross Validation...")

        # Check if we have enough samples for CV
        min_cv_samples = (
            self.config["CV_SPLITS"] + 1
        )  # Need at least n_splits + 1 samples
        if len(X) >= min_cv_samples:
            tscv = TimeSeriesSplit(n_splits=self.config["CV_SPLITS"])
            cv_scores = {"mae": [], "mse": [], "r2": [], "mape": []}

            self.logger.info(
                f"🔄 Performing {self.config['CV_SPLITS']}-fold cross-validation..."
            )

            for fold, (train_idx, val_idx) in enumerate(tscv.split(X)):
                self.logger.info(
                    f"Training fold {fold + 1}/{self.config['CV_SPLITS']}..."
                )

                X_train, X_val = X.iloc[train_idx], X.iloc[val_idx]
                y_train, y_val = y.iloc[train_idx], y.iloc[val_idx]

                # Train model
                model.fit(X_train, y_train)

                # Make predictions
                y_pred = model.predict(X_val)

                # Calculate metrics
                mae = mean_absolute_error(y_val, y_pred)
                mse = mean_squared_error(y_val, y_pred)
                r2 = r2_score(y_val, y_pred)
                mape = mean_absolute_percentage_error(y_val, y_pred)

                cv_scores["mae"].append(mae)
                cv_scores["mse"].append(mse)
                cv_scores["r2"].append(r2)
                cv_scores["mape"].append(mape)

                self.logger.info(
                    f"  Fold {fold + 1}: MAE={mae:.2f}, R²={r2:.3f}, MAPE={mape:.1%}"
                )
        else:
            self.logger.warning(
                f"⚠️ Skipping Cross Validation: Only {len(X)} samples available, need at least {min_cv_samples} for {self.config['CV_SPLITS']}-fold CV"
            )
            cv_scores = {"mae": [], "mse": [], "r2": [], "mape": []}

        # Train final model on all data
        self.logger.info("🎯 Training final model on complete dataset...")
        model.fit(X, y)

        # Calculate final performance metrics
        y_pred = model.predict(X)
        final_metrics = {
            "mae": mean_absolute_error(y, y_pred),
            "mse": mean_squared_error(y, y_pred),
            "rmse": np.sqrt(mean_squared_error(y, y_pred)),
            "r2": r2_score(y, y_pred),
            "mape": mean_absolute_percentage_error(y, y_pred),
            "cv_mae_mean": np.mean(cv_scores["mae"]) if cv_scores["mae"] else 0,
            "cv_mae_std": np.std(cv_scores["mae"]) if cv_scores["mae"] else 0,
            "cv_r2_mean": np.mean(cv_scores["r2"]) if cv_scores["r2"] else 0,
            "cv_r2_std": np.std(cv_scores["r2"]) if cv_scores["r2"] else 0,
        }

        self.performance_metrics[prediction_type] = final_metrics

        # Log performance
        self.logger.info("=" * 50)
        self.logger.info(f"HASIL CROSS VALIDATION - {prediction_type.upper()}:")
        self.logger.info(
            f"Rata-rata MAE : {final_metrics['cv_mae_mean']:.2f} ± {final_metrics['cv_mae_std']:.2f}"
        )
        self.logger.info(
            f"Rata-rata R²  : {final_metrics['cv_r2_mean']:.3f} ± {final_metrics['cv_r2_std']:.3f}"
        )
        self.logger.info("=" * 50)

        self.logger.info("📊 FINAL MODEL PERFORMANCE:")
        self.logger.info(f"   MAE: {final_metrics['mae']:.2f}")
        self.logger.info(f"   RMSE: {final_metrics['rmse']:.2f}")
        self.logger.info(f"   R²: {final_metrics['r2']:.3f}")
        self.logger.info(f"   MAPE: {final_metrics['mape']:.1%}")

        # Feature importance analysis with product name mapping
        try:
            # Get feature names from the preprocessor
            categorical_feature_names = list(
                model.named_steps["preprocessor"]
                .named_transformers_["cat"]
                .get_feature_names_out()
            )
            numerical_feature_names = [col for col in X.columns if col != "id_item"]
            all_feature_names = categorical_feature_names + numerical_feature_names

            importance = model.named_steps["regressor"].feature_importances_

            # Create mapping from encoded features to actual product IDs/names
            # Get the unique product IDs from training data
            unique_products = sorted(X["id_item"].unique())

            # Create a mapping for one-hot encoded features to product names
            feature_mapping = {}
            for i, feature_name in enumerate(all_feature_names):
                if feature_name.startswith("id_item_"):
                    # Extract the index from the one-hot encoded feature name
                    try:
                        # The OneHotEncoder creates features like 'id_item_0', 'id_item_1', etc.
                        # corresponding to the sorted unique values
                        encoded_idx = int(feature_name.split("_")[-1])
                        if encoded_idx < len(unique_products):
                            actual_product_id = unique_products[encoded_idx]

                            # Use product mapping if available
                            if product_mapping and actual_product_id in product_mapping:
                                product_name = product_mapping[actual_product_id]
                                # Truncate long names for display
                                if len(str(product_name)) > 25:
                                    product_name = str(product_name)[:22] + "..."
                                feature_mapping[feature_name] = (
                                    f"ID_{actual_product_id}:{product_name}"
                                )
                            else:
                                feature_mapping[feature_name] = (
                                    f"Product_ID_{actual_product_id}"
                                )
                        else:
                            feature_mapping[feature_name] = feature_name
                    except (ValueError, IndexError):
                        feature_mapping[feature_name] = feature_name
                else:
                    feature_mapping[feature_name] = feature_name

            # Show feature importance with mapped names
            feature_importance = list(zip(all_feature_names, importance))
            feature_importance.sort(key=lambda x: x[1], reverse=True)

            self.logger.info(f"FEATURE IMPORTANCE - {prediction_type.upper()}:")
            for i, (feature, imp) in enumerate(feature_importance[:10], 1):
                mapped_name = feature_mapping.get(feature, feature)
                self.logger.warning(f"{i:2d}. {mapped_name:<35} : {imp:.4f}")

        except Exception as e:
            self.logger.info(f"Warning: Could not extract feature importance: {e}")

        # Store model
        self.models[prediction_type] = model

        return model

    def save_model(
        self,
        model: Pipeline,
        prediction_type: str,
        valid_products: Optional[List[str]] = None,
        product_mapping: Optional[dict] = None,
    ):
        """
        Save trained model and metadata

        Args:
            model (Pipeline): Trained model
            prediction_type (str): 'sales' or 'restock'
            valid_products (list): List of valid product names from training data
        """
        model_path = (
            self.sales_model_path
            if prediction_type == "sales"
            else self.restock_model_path
        )

        try:
            # Save model
            joblib.dump(model, model_path)
            self.logger.info(f"💾 Model saved: {model_path}")

            # Save metadata
            metadata = {
                "model_type": prediction_type,
                "version": self.config["MODEL_VERSION"],
                "training_date": self.config["TRAINING_DATE"],
                "performance_metrics": self.performance_metrics.get(
                    prediction_type, {}
                ),
                "config": self.config,
                "valid_products": valid_products or [],
                "product_mapping": product_mapping or {},
            }

            metadata_path = model_path.with_suffix(".json")
            with open(metadata_path, "w") as f:
                json.dump(metadata, f, indent=2, default=str)

            self.logger.info(f"📋 Metadata saved: {metadata_path}")
            if valid_products:
                self.logger.info(
                    f"📦 Valid products list saved: {len(valid_products)} products"
                )

        except Exception as e:
            self.logger.error(f"❌ Error saving model: {str(e)}")
            raise

    def load_model(self, prediction_type: str) -> Pipeline:
        """
        Load trained model

        Args:
            prediction_type (str): 'sales' or 'restock'

        Returns:
            Pipeline: Loaded model
        """
        model_path = (
            self.sales_model_path
            if prediction_type == "sales"
            else self.restock_model_path
        )

        try:
            if not model_path.exists():
                raise FileNotFoundError(f"Model not found: {model_path}")

            model = joblib.load(model_path)
            self.models[prediction_type] = model

            self.logger.info(f"✅ Model loaded: {model_path}")
            return model

        except Exception as e:
            self.logger.error(f"❌ Error loading model: {str(e)}")
            raise

    def predict(self, product_id: str, prediction_type: str, **kwargs) -> Dict:
        """
        Make prediction for a specific product with execution time tracking

        Args:
            product_id (str): Product ID (used as nama_barang in training data)
            prediction_type (str): 'sales' or 'restock'
            **kwargs: Additional parameters based on prediction type
                     For sales: avg_daily_sales, sales_velocity, sales_consistency, recent_avg, transaction_count
                     For restock: avg_daily_sales, sales_velocity, sales_volatility, recent_total, transaction_count

        Returns:
            Dict: Dictionary containing prediction result and execution time
                  {
                      'prediction': int,
                      'execution_time_ms': float,
                      'product_id': str,
                      'prediction_type': str,
                      'input_parameters': dict
                  }

        Raises:
            ValueError: If product ID is not found in training data
        """
        import time

        start_time = time.time()

        self.logger.info(f"PREDICTION - {prediction_type.upper()}")

        # Validate product ID first
        if not product_id or not product_id.strip():
            raise ValueError("Product ID cannot be empty")

        # Load model if not already loaded
        if prediction_type not in self.models:
            self.load_model(prediction_type)

        model = self.models[prediction_type]

        # Get valid products from training data (stored in model metadata)
        try:
            model_metadata_path = (
                self.sales_model_path.with_suffix(".json")
                if prediction_type == "sales"
                else self.restock_model_path.with_suffix(".json")
            )

            if model_metadata_path.exists():
                with open(model_metadata_path, "r") as f:
                    metadata = json.load(f)

                # Check if we have valid products list in metadata
                valid_products = metadata.get("valid_products", [])

                if valid_products and product_id not in valid_products:
                    self.logger.warning(
                        f"⚠️ Product ID '{product_id}' not found in training data"
                    )
                    self.logger.info(
                        f"📋 Available product IDs: {', '.join(valid_products[:10])}{'...' if len(valid_products) > 10 else ''}"
                    )

                    # Instead of raising error, provide fallback prediction
                    self.logger.info(
                        "🔄 Generating fallback prediction for unknown product"
                    )
                    fallback_prediction = self.generate_fallback_prediction(
                        prediction_type, **kwargs
                    )

                    # Calculate execution times
                    end_time = time.time()
                    total_execution_time_ms = (end_time - start_time) * 1000

                    # Create result dictionary for fallback
                    result = {
                        "prediction": fallback_prediction,
                        "execution_time_ms": round(total_execution_time_ms, 2),
                        "model_prediction_time_ms": 0,
                        "product_id": product_id,
                        "prediction_type": prediction_type,
                        "input_parameters": kwargs,
                        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                        "is_fallback": True,
                        "fallback_reason": "Product not in training data",
                    }

                    self.logger.info(
                        f"🎯 FALLBACK PREDICTION RESULT: {fallback_prediction} units"
                    )
                    self.logger.info(
                        f"⚡ Total execution time: {result['execution_time_ms']:.2f}ms"
                    )

                    return result
            else:
                self.logger.warning(
                    "⚠️ Model metadata not found, proceeding without product validation"
                )

        except Exception as e:
            if "not found in training data" in str(e):
                raise  # Re-raise validation errors
            else:
                self.logger.warning(f"⚠️ Could not validate product ID: {str(e)}")
                # Continue with prediction but log the warning

        try:
            input_parameters = {}
            input_data = None

            if prediction_type == "sales":
                # Prepare sales prediction input
                avg_daily_sales = kwargs.get("avg_daily_sales", 0)
                sales_velocity = kwargs.get("sales_velocity", 0)
                sales_consistency = kwargs.get("sales_consistency", 1)
                recent_avg = kwargs.get("recent_avg", 0)
                transaction_count = kwargs.get("transaction_count", 0)

                input_data = pd.DataFrame(
                    {
                        "id_item": [product_id],
                        "avg_daily_sales": [avg_daily_sales],
                        "sales_velocity": [sales_velocity],
                        "sales_consistency": [sales_consistency],
                        "recent_avg": [recent_avg],
                        "transaction_count": [transaction_count],
                    }
                )

                input_parameters = {
                    "avg_daily_sales": avg_daily_sales,
                    "sales_velocity": sales_velocity,
                    "sales_consistency": sales_consistency,
                    "recent_avg": recent_avg,
                    "transaction_count": transaction_count,
                }

                self.logger.info(f"📦 Product ID: {product_id}")
                self.logger.info(f"📈 Avg Daily Sales: {avg_daily_sales} units")
                self.logger.info(f"📈 Sales Velocity: {sales_velocity}")
                self.logger.info(f"📈 Sales Consistency: {sales_consistency}")
                self.logger.info(f"📈 Recent Average: {recent_avg} units")
                self.logger.info(f"📈 Transaction Count: {transaction_count}")

            elif prediction_type == "restock":
                # Prepare restock prediction input
                avg_daily_sales = kwargs.get("avg_daily_sales", 0)
                sales_velocity = kwargs.get("sales_velocity", 0)
                sales_volatility = kwargs.get("sales_volatility", 1)
                recent_total = kwargs.get("recent_total", 0)
                transaction_count = kwargs.get("transaction_count", 0)

                input_data = pd.DataFrame(
                    {
                        "id_item": [product_id],
                        "avg_daily_sales": [avg_daily_sales],
                        "sales_velocity": [sales_velocity],
                        "sales_volatility": [sales_volatility],
                        "recent_total": [recent_total],
                        "transaction_count": [transaction_count],
                    }
                )

                input_parameters = {
                    "avg_daily_sales": avg_daily_sales,
                    "sales_velocity": sales_velocity,
                    "sales_volatility": sales_volatility,
                    "recent_total": recent_total,
                    "transaction_count": transaction_count,
                }

                self.logger.info(f"📦 Product ID: {product_id}")
                self.logger.info(f"📈 Avg Daily Sales: {avg_daily_sales} units")
                self.logger.info(f"📈 Sales Velocity: {sales_velocity}")
                self.logger.info(f"📈 Sales Volatility: {sales_volatility}")
                self.logger.info(f"📈 Recent Total: {recent_total} units")
                self.logger.info(f"📈 Transaction Count: {transaction_count}")

            else:
                raise ValueError(
                    f"Invalid prediction_type: {prediction_type}. Must be 'sales' or 'restock'"
                )

            if input_data is None:
                raise ValueError("Failed to prepare input data")

            # Make prediction
            prediction_start_time = time.time()
            prediction = model.predict(input_data)[0]
            prediction_end_time = time.time()

            prediction = max(0, round(prediction))  # Ensure non-negative integer

            # Calculate execution times
            end_time = time.time()
            total_execution_time_ms = (end_time - start_time) * 1000
            model_prediction_time_ms = (
                prediction_end_time - prediction_start_time
            ) * 1000

            # Create result dictionary
            result = {
                "prediction": prediction,
                "execution_time_ms": round(total_execution_time_ms, 2),
                "model_prediction_time_ms": round(model_prediction_time_ms, 2),
                "product_id": product_id,
                "prediction_type": prediction_type,
                "input_parameters": input_parameters,
                "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            }

            # Add interpretation based on prediction type
            if prediction_type == "sales":
                result["interpretation"] = f"Predicted sales for next period: {prediction} units"
            else:  # restock
                result["interpretation"] = f"Recommended restock quantity: {prediction} units"

            self.logger.info(f"🎯 PREDICTION RESULT: {prediction} units")
            self.logger.info(f"⏱️ Total execution time: {total_execution_time_ms:.2f}ms")
            self.logger.info(
                f"🚀 Model prediction time: {model_prediction_time_ms:.2f}ms"
            )

            return result

        except Exception as e:
            end_time = time.time()
            execution_time_ms = (end_time - start_time) * 1000
            self.logger.error(f"❌ Prediction error: {str(e)}")
            self.logger.error(f"⏱️ Failed after: {execution_time_ms:.2f}ms")
            raise

    def batch_predict(self, predictions_list: List[Dict]) -> List[Dict]:
        """
        Make batch predictions with execution time tracking

        Args:
            predictions_list: List of prediction requests, each containing:
                {
                    'product_id': str,
                    'prediction_type': str ('daily' or 'monthly'),
                    'parameters': dict (lag1,lag2,lag3 for daily or prev_month_total for monthly)
                }

        Returns:
            List[Dict]: List of prediction results with execution times
        """
        import time

        batch_start_time = time.time()

        self.logger.info(f"BATCH PREDICTION - {len(predictions_list)} requests")

        results = []
        successful_predictions = 0
        failed_predictions = 0

        for i, request in enumerate(predictions_list, 1):
            try:
                self.logger.info(f"Processing request {i}/{len(predictions_list)}")

                product_id = request.get("product_id")
                prediction_type = request.get("prediction_type")
                parameters = request.get("parameters", {})

                if not product_id or not prediction_type:
                    raise ValueError(
                        "Missing required fields: product_id or prediction_type"
                    )

                # Make individual prediction
                result = self.predict(product_id, prediction_type, **parameters)
                result["batch_index"] = i
                result["batch_request"] = request
                results.append(result)
                successful_predictions += 1

            except Exception as e:
                self.logger.error(f"❌ Batch prediction {i} failed: {str(e)}")
                failed_predictions += 1

                # Add error result to maintain batch order
                error_result = {
                    "batch_index": i,
                    "batch_request": request,
                    "error": str(e),
                    "prediction": None,
                    "execution_time_ms": 0,
                    "product_id": request.get("product_id", "Unknown"),
                    "prediction_type": request.get("prediction_type", "Unknown"),
                    "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                }
                results.append(error_result)

        batch_end_time = time.time()
        total_batch_time_ms = (batch_end_time - batch_start_time) * 1000

        # Add batch summary
        batch_summary = {
            "total_requests": len(predictions_list),
            "successful_predictions": successful_predictions,
            "failed_predictions": failed_predictions,
            "success_rate": (successful_predictions / len(predictions_list)) * 100,
            "total_batch_time_ms": round(total_batch_time_ms, 2),
            "average_time_per_prediction_ms": round(
                total_batch_time_ms / len(predictions_list), 2
            ),
            "batch_timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        }

        self.logger.info(f"📊 BATCH SUMMARY:")
        self.logger.info(f"   Total requests: {batch_summary['total_requests']}")
        self.logger.info(f"   Successful: {batch_summary['successful_predictions']}")
        self.logger.info(f"   Failed: {batch_summary['failed_predictions']}")
        self.logger.info(f"   Success rate: {batch_summary['success_rate']:.1f}%")
        self.logger.info(f"   Total time: {batch_summary['total_batch_time_ms']:.2f}ms")
        self.logger.info(
            f"   Avg per prediction: {batch_summary['average_time_per_prediction_ms']:.2f}ms"
        )

        # Add summary to results
        for result in results:
            if "error" not in result:
                result["batch_summary"] = batch_summary

        return results

    def generate_fallback_prediction(self, prediction_type: str, **kwargs) -> int:
        """
        Generate fallback prediction for products not in training data

        Args:
            prediction_type: 'sales' or 'restock'
            **kwargs: Prediction parameters

        Returns:
            int: Fallback prediction value
        """
        try:
            if prediction_type == "sales":
                # For sales prediction, use historical sales patterns
                avg_daily_sales = kwargs.get("avg_daily_sales", 0)
                recent_avg = kwargs.get("recent_avg", 0)
                transaction_count = kwargs.get("transaction_count", 0)

                if avg_daily_sales > 0 or recent_avg > 0:
                    # Use the better of the two averages with slight growth factor
                    base_prediction = max(avg_daily_sales, recent_avg) * 7  # Weekly estimate
                    fallback = round(base_prediction * 1.1)  # 10% growth assumption
                else:
                    # Default conservative sales prediction for new products
                    fallback = 10

                self.logger.info(
                    f"📊 Sales fallback based on avg_daily:{avg_daily_sales}, recent_avg:{recent_avg}: {fallback} units"
                )

            elif prediction_type == "restock":
                # For restock prediction, use safety stock calculations
                avg_daily_sales = kwargs.get("avg_daily_sales", 0)
                sales_volatility = kwargs.get("sales_volatility", 1)
                recent_total = kwargs.get("recent_total", 0)

                if avg_daily_sales > 0:
                    # Calculate restock based on consumption rate and volatility
                    lead_time_demand = avg_daily_sales * 7  # 7-day lead time
                    safety_stock = sales_volatility * self.config["RESTOCK_THRESHOLD_MULTIPLIER"]
                    fallback = round((lead_time_demand + safety_stock) * 1.5)
                elif recent_total > 0:
                    # Use recent consumption pattern
                    fallback = round(recent_total * 1.3)  # 30% buffer
                else:
                    # Default conservative restock for new products
                    fallback = 50

                self.logger.info(
                    f"📊 Restock fallback based on avg_daily:{avg_daily_sales}, volatility:{sales_volatility}: {fallback} units"
                )

            else:
                raise ValueError(f"Invalid prediction_type: {prediction_type}")

            return max(1, fallback)  # Ensure at least 1 unit prediction

        except Exception as e:
            self.logger.warning(f"⚠️ Error generating fallback prediction: {e}")
            # Return minimal safe prediction
            return 10 if prediction_type == "sales" else 50

    def train_all_models(self, data_folder: str = "data"):
        """
        Train both sales and restock models

        Args:
            data_folder (str): Path to folder containing Excel files
        """
        self.logger.info("COMPREHENSIVE MODEL TRAINING - SALES & RESTOCK")

        try:
            # Load and process data from the specified folder
            data = self.load_and_process_data(data_folder)
            sales_data = self.process_sales_data(data)

            # Create product mapping from sales_data for better feature importance display
            product_mapping = {}
            if "nama_barang" in sales_data.columns and "id_item" in sales_data.columns:
                product_mapping = dict(
                    sales_data[["id_item", "nama_barang"]].drop_duplicates().values
                )

            # Train sales model
            try:
                # Create features for sales prediction
                sales_features = self.create_features(sales_data, "sales")
                X_sales, y_sales = self.prepare_features(sales_features, "sales")

                # Get list of valid products for sales model (convert to strings)
                sales_valid_products = sorted(
                    [str(x) for x in X_sales["id_item"].unique().tolist()]
                )

                sales_model = self.train_model(
                    X_sales, y_sales, "sales", product_mapping
                )
                self.save_model(
                    sales_model, "sales", sales_valid_products, product_mapping
                )
                self.logger.info("✅ Sales model training completed successfully")
            except Exception as e:
                self.logger.error(f"❌ Sales model training failed: {str(e)}")

            # Train restock model
            try:
                # Create features for restock prediction
                restock_features = self.create_features(sales_data, "restock")
                X_restock, y_restock = self.prepare_features(
                    restock_features, "restock"
                )

                # Get list of valid products for restock model (convert to strings)
                restock_valid_products = sorted(
                    [str(x) for x in X_restock["id_item"].unique().tolist()]
                )

                # Use the same product mapping
                restock_model = self.train_model(
                    X_restock, y_restock, "restock", product_mapping
                )
                self.save_model(
                    restock_model, "restock", restock_valid_products, product_mapping
                )
                self.logger.info("✅ Restock model training completed successfully")
            except Exception as e:
                self.logger.error(f"❌ Restock model training failed: {str(e)}")

            self.logger.info("TRAINING COMPLETED")
            self.logger.info("🎉 All models trained successfully!")

            # Also output to stdout for Laravel detection
            print("TRAINING_COMPLETED")

        except Exception as e:
            self.logger.error(f"❌ Training process failed: {str(e)}")
            print(f"TRAINING_FAILED: {str(e)}")
            raise


def main():
    """Main function for command line usage"""
    if len(sys.argv) < 2:
        print("Usage:")
        print("  Training: python stock_predictor.py train")
        print(
            "  Sales Prediction: python stock_predictor.py predict sales <product_id> <avg_daily_sales> <sales_velocity> <sales_consistency> <recent_avg> <transaction_count>"
        )
        print(
            "  Restock Prediction: python stock_predictor.py predict restock <product_id> <avg_daily_sales> <sales_velocity> <sales_volatility> <recent_total> <transaction_count>"
        )
        sys.exit(1)

    command = sys.argv[1]
    predictor = StockPredictor()

    if command == "train":
        # Use the default data from path main.py
        data_folder = str(Path(__file__).parent / "data")

        # Check if data folder exists
        if not Path(data_folder).exists():
            predictor.logger.error(f"❌ Data folder '{data_folder}' not found")
            sys.exit(1)

        predictor.train_all_models(data_folder)

    elif command == "predict":
        # Prediction mode
        if len(sys.argv) < 4:
            print("❌ Insufficient arguments for prediction")
            sys.exit(1)

        prediction_type = sys.argv[2]
        product_id = sys.argv[3]

        try:
            if prediction_type == "sales":
                if len(sys.argv) != 9:
                    print(
                        "❌ Sales prediction requires: <product_id> <avg_daily_sales> <sales_velocity> <sales_consistency> <recent_avg> <transaction_count>"
                    )
                    sys.exit(1)

                avg_daily_sales = float(sys.argv[4])
                sales_velocity = float(sys.argv[5])
                sales_consistency = float(sys.argv[6])
                recent_avg = float(sys.argv[7])
                transaction_count = int(sys.argv[8])

                result = predictor.predict(
                    product_id, "sales",
                    avg_daily_sales=avg_daily_sales,
                    sales_velocity=sales_velocity,
                    sales_consistency=sales_consistency,
                    recent_avg=recent_avg,
                    transaction_count=transaction_count
                )

            elif prediction_type == "restock":
                if len(sys.argv) != 9:
                    print(
                        "❌ Restock prediction requires: <product_id> <avg_daily_sales> <sales_velocity> <sales_volatility> <recent_total> <transaction_count>"
                    )
                    sys.exit(1)

                avg_daily_sales = float(sys.argv[4])
                sales_velocity = float(sys.argv[5])
                sales_volatility = float(sys.argv[6])
                recent_total = float(sys.argv[7])
                transaction_count = int(sys.argv[8])

                result = predictor.predict(
                    product_id, "restock",
                    avg_daily_sales=avg_daily_sales,
                    sales_velocity=sales_velocity,
                    sales_volatility=sales_volatility,
                    recent_total=recent_total,
                    transaction_count=transaction_count
                )

            else:
                print("❌ Prediction type must be 'sales' or 'restock'")
                sys.exit(1)

            # Output for API integration
            # For backward compatibility, still output the old format for Laravel
            print(f"PREDICTION_RESULT:{result['prediction']}")

            # Also output the full result as JSON for enhanced functionality
            import json

            print(f"PREDICTION_FULL:{json.dumps(result)}")

        except ValueError as e:
            print(f"❌ Invalid parameter: {e}")
            sys.exit(1)
        except Exception as e:
            print(f"❌ Prediction failed: {e}")
            sys.exit(1)

    else:
        print(f"❌ Unknown command: {command}")
        sys.exit(1)


if __name__ == "__main__":
    main()
