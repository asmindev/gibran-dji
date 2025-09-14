# utils.py - Utility Functions for Stock Prediction System
"""
Utility classes and functions for the Stock Prediction System

This module contains all utility functions for data processing,
logging, validation, and other helper functions.

Author: Stock Prediction System
Version: 4.0
"""

import os
import sys
import pandas as pd
import numpy as np
import logging
import warnings
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Optional, Tuple

# Suppress warnings
warnings.filterwarnings("ignore")


class DataUtils:
    """Utility class for data processing operations"""

    @staticmethod
    def convert_indonesian_date(date_str):
        """Convert Indonesian month names to English"""
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
        except Exception:
            return date_str

    @staticmethod
    def validate_data_folder(data_folder: str) -> List[str]:
        """Validate data folder and return data files"""
        if not os.path.exists(data_folder):
            raise FileNotFoundError(f"Data folder '{data_folder}' not found")

        all_files = os.listdir(data_folder)
        data_files = [f for f in all_files if f.endswith((".xlsx", ".xls", ".csv"))]

        if not data_files:
            raise FileNotFoundError(f"No data files found in folder '{data_folder}'")

        return data_files

    @staticmethod
    def load_file(file_path: str) -> pd.DataFrame:
        """Load Excel or CSV file"""
        if file_path.endswith((".xlsx", ".xls")):
            return pd.read_excel(file_path, skiprows=1)
        elif file_path.endswith(".csv"):
            return pd.read_csv(file_path)
        else:
            raise ValueError(f"Unsupported file format: {file_path}")

    @staticmethod
    def standardize_columns(df: pd.DataFrame, expected_cols: List[str]) -> pd.DataFrame:
        """Standardize DataFrame columns"""
        if len(df.columns) < len(expected_cols):
            raise ValueError(
                f"Expected {len(expected_cols)} columns, got {len(df.columns)}"
            )

        df.columns = expected_cols[: len(df.columns)]
        return df

    @staticmethod
    def clean_numeric_column(df: pd.DataFrame, column: str) -> pd.DataFrame:
        """Clean and convert column to numeric"""
        df = df.copy()
        df[column] = pd.to_numeric(df[column], errors="coerce")
        df = df.dropna(subset=[column])
        df = df[df[column] > 0]  # Remove zero or negative values
        return df

    @staticmethod
    def process_dates(df: pd.DataFrame, date_column: str) -> pd.DataFrame:
        """Process date column with Indonesian date conversion"""
        df = df.copy()
        df[date_column] = df[date_column].apply(DataUtils.convert_indonesian_date)

        try:
            df[date_column] = pd.to_datetime(
                df[date_column], format="mixed", dayfirst=True
            )
        except Exception:
            df[date_column] = pd.to_datetime(
                df[date_column], errors="coerce", dayfirst=True
            )

        df = df.dropna(subset=[date_column])
        return df


class LoggingUtils:
    """Utility class for logging setup and management"""

    @staticmethod
    def setup_logger(name: str, log_file: Path) -> logging.Logger:
        """Setup logger with file output"""
        # Create log directory if it doesn't exist
        log_file.parent.mkdir(exist_ok=True)

        # Configure logging
        logging.basicConfig(
            level=logging.INFO,
            format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
            handlers=[
                logging.FileHandler(log_file, encoding="utf-8"),
            ],
        )

        logger = logging.getLogger(name)
        logger.info(f"Logging initialized - Log file: {log_file}")
        return logger


class ValidationUtils:
    """Utility class for data validation"""

    @staticmethod
    def validate_required_columns(df: pd.DataFrame, required_cols: List[str]) -> bool:
        """Check if DataFrame has required columns"""
        missing_cols = [col for col in required_cols if col not in df.columns]
        if missing_cols:
            raise ValueError(f"Missing required columns: {missing_cols}")
        return True

    @staticmethod
    def validate_product_id(product_id: str, valid_products: List[str]) -> bool:
        """Validate if product ID exists in training data"""
        if not product_id or not product_id.strip():
            raise ValueError("Product ID cannot be empty")

        if valid_products and product_id not in valid_products:
            return False
        return True

    @staticmethod
    def get_data_quality_metrics(df: pd.DataFrame, file_name: str) -> Dict:
        """Get data quality metrics for a DataFrame"""
        return {
            "file_name": file_name,
            "total_rows": len(df),
            "total_columns": len(df.columns),
            "missing_values": df.isnull().sum().sum(),
            "duplicate_rows": df.duplicated().sum(),
            "memory_usage_mb": df.memory_usage(deep=True).sum() / 1024 / 1024,
        }


class FeatureUtils:
    """Utility class for feature engineering"""

    @staticmethod
    def create_sales_features(sales_data: pd.DataFrame) -> pd.DataFrame:
        """Create features from sales data"""
        # Aggregate sales by product and date
        sales_agg = (
            sales_data.groupby(["id_item", "tgl"])
            .agg({"qty_sold": "sum"})
            .reset_index()
        )

        # Sort by product and date
        sales_agg = sales_agg.sort_values(["id_item", "tgl"])

        # Create lag features (previous sales)
        sales_agg["prev_sales_1"] = sales_agg.groupby("id_item")["qty_sold"].shift(1)
        sales_agg["prev_sales_7"] = sales_agg.groupby("id_item")["qty_sold"].shift(7)
        sales_agg["prev_sales_30"] = sales_agg.groupby("id_item")["qty_sold"].shift(30)

        # Create moving averages
        sales_agg["avg_sales_7"] = (
            sales_agg.groupby("id_item")["qty_sold"]
            .rolling(7, min_periods=1)
            .mean()
            .reset_index(0, drop=True)
        )
        sales_agg["avg_sales_30"] = (
            sales_agg.groupby("id_item")["qty_sold"]
            .rolling(30, min_periods=1)
            .mean()
            .reset_index(0, drop=True)
        )

        return sales_agg

    @staticmethod
    def create_restock_features(restock_data: pd.DataFrame) -> pd.DataFrame:
        """Create features from restock data"""
        # Aggregate restock by product and date
        restock_agg = (
            restock_data.groupby(["id_item", "tgl"])
            .agg({"qty_restock": "sum"})
            .reset_index()
        )

        # Sort by product and date
        restock_agg = restock_agg.sort_values(["id_item", "tgl"])

        # Create lag features (previous restocks)
        restock_agg["prev_restock_1"] = restock_agg.groupby("id_item")[
            "qty_restock"
        ].shift(1)
        restock_agg["prev_restock_7"] = restock_agg.groupby("id_item")[
            "qty_restock"
        ].shift(7)

        # Days since last restock
        restock_agg["days_since_restock"] = (
            restock_agg.groupby("id_item")["tgl"].diff().dt.days
        )

        return restock_agg

    @staticmethod
    def merge_sales_restock_features(
        sales_features: pd.DataFrame, restock_features: pd.DataFrame
    ) -> pd.DataFrame:
        """Merge sales and restock features"""
        # Merge on product and date
        merged = pd.merge(
            sales_features, restock_features, on=["id_item", "tgl"], how="outer"
        )

        # Fill missing values
        merged["qty_sold"] = merged["qty_sold"].fillna(0)
        merged["qty_restock"] = merged["qty_restock"].fillna(0)

        # Forward fill lag features within each product
        for col in merged.columns:
            if "prev_" in col or "avg_" in col or "days_since" in col:
                merged[col] = merged.groupby("id_item")[col].fillna(method="ffill")

        # Fill remaining NaN with 0
        merged = merged.fillna(0)

        return merged


class ModelUtils:
    """Utility class for model operations"""

    @staticmethod
    def calculate_metrics(y_true: np.ndarray, y_pred: np.ndarray) -> Dict:
        """Calculate prediction metrics"""
        from sklearn.metrics import mean_absolute_error, r2_score, mean_squared_error

        try:
            from sklearn.metrics import mean_absolute_percentage_error
        except ImportError:
            # Fallback for older sklearn versions
            def mean_absolute_percentage_error(y_true, y_pred):
                return (
                    np.mean(
                        np.abs((y_true - y_pred) / np.maximum(np.abs(y_true), 1e-8))
                    )
                    * 100
                )

        return {
            "mae": mean_absolute_error(y_true, y_pred),
            "rmse": np.sqrt(mean_squared_error(y_true, y_pred)),
            "r2": r2_score(y_true, y_pred),
            "mape": mean_absolute_percentage_error(y_true, y_pred),
        }

    @staticmethod
    def generate_fallback_prediction(prediction_type: str, **kwargs) -> int:
        """Generate fallback prediction for unknown products"""
        if prediction_type == "sales":
            # Base prediction on historical sales patterns
            prev_sales = kwargs.get("prev_sales", 0)
            avg_sales = kwargs.get("avg_sales", 5)

            if prev_sales > 0:
                return max(1, int(prev_sales * 0.9))  # Slight decrease assumption
            else:
                return max(1, int(avg_sales))

        elif prediction_type == "restock":
            # Base prediction on current stock level and sales
            current_stock = kwargs.get("current_stock", 0)
            avg_sales = kwargs.get("avg_sales", 5)

            # Suggest restock if stock is low
            if current_stock < avg_sales * 7:  # Less than 7 days of sales
                return max(10, int(avg_sales * 14))  # 14 days worth
            else:
                return 0  # No restock needed

        return 5  # Default fallback


class ConfigUtils:
    """Utility class for configuration management"""

    @staticmethod
    def get_default_config() -> Dict:
        """Get default configuration"""
        return {
            "MODEL_VERSION": "4.0",
            "TRAINING_DATE": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "MIN_SAMPLES": 3,
            "RANDOM_STATE": 42,
            "CV_SPLITS": 3,
            "N_ESTIMATORS": 100,
            "MAX_DEPTH": 8,
        }

    @staticmethod
    def create_directory_structure(base_path: Path) -> Dict[str, Path]:
        """Create and return directory structure"""
        directories = {
            "model": base_path / "models",
            "data": base_path / "data",
            "logs": base_path / "logs",
        }

        for dir_path in directories.values():
            dir_path.mkdir(exist_ok=True)

        return directories
