# stock_predictor.py - Main Stock Prediction System
"""
Simplified Stock Prediction System

A clean, modular system for predicting sales and restock requirements
using machine learning algorithms with proper separation of concerns.

Features:
- Sales prediction based on historical patterns
- Restock prediction based on inventory and sales
- Modular architecture with utilities separation
- Simple and focused functionality
- Professional logging and error handling

Author: Stock Prediction System
Version: 4.0
Date: September 2025
"""

import os
import sys
import pandas as pd
import numpy as np
import joblib
import json
import time
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Tuple
from pathlib import Path

# Machine Learning imports
from sklearn.compose import ColumnTransformer
from sklearn.preprocessing import OneHotEncoder, StandardScaler
from sklearn.pipeline import Pipeline
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import TimeSeriesSplit

# Import utilities
from .utils import (
    DataUtils,
    LoggingUtils,
    ValidationUtils,
    FeatureUtils,
    ModelUtils,
    ConfigUtils,
)


class StockDataProcessor:
    """Handles data loading and processing for stock prediction"""

    def __init__(self, logger):
        self.logger = logger
        self.data_utils = DataUtils()
        self.validation_utils = ValidationUtils()

    def load_sales_data(self, data_folder: str) -> pd.DataFrame:
        """Load and process sales data"""
        self.logger.info("Loading sales data...")

        files = self.data_utils.validate_data_folder(data_folder)
        sales_files = [
            f for f in files if "sales" in f.lower() or "penjualan" in f.lower()
        ]

        if not sales_files:
            # If no specific sales files, try to load all files and filter
            self.logger.warning("No specific sales files found, loading all data files")
            sales_files = files

        all_sales = []
        for file in sales_files:
            try:
                file_path = os.path.join(data_folder, file)
                df = self.data_utils.load_file(file_path)

                # Standardize columns for sales data
                expected_cols = [
                    "no",
                    "id_trx",
                    "tgl",
                    "id_item",
                    "nama_barang",
                    "kategori",
                    "jumlah",
                ]
                df = self.data_utils.standardize_columns(df, expected_cols)

                # Filter for sales transactions (keluar)
                sales_mask = df["kategori"].str.lower().str.contains("keluar", na=False)
                sales = df[sales_mask].copy()

                if not sales.empty:
                    # Rename and clean quantity
                    sales = sales.rename(columns={"jumlah": "qty_sold"})
                    sales = self.data_utils.clean_numeric_column(sales, "qty_sold")
                    sales = self.data_utils.process_dates(sales, "tgl")

                    all_sales.append(sales)
                    self.logger.info(f"Loaded {len(sales)} sales records from {file}")

            except Exception as e:
                self.logger.error(f"Failed to load {file}: {str(e)}")
                continue

        if not all_sales:
            raise ValueError("No valid sales data found")

        combined_sales = pd.concat(all_sales, ignore_index=True)
        self.logger.info(f"Total sales records loaded: {len(combined_sales)}")

        return combined_sales

    def load_restock_data(self, data_folder: str) -> pd.DataFrame:
        """Load and process restock data"""
        self.logger.info("Loading restock data...")

        files = self.data_utils.validate_data_folder(data_folder)
        restock_files = [
            f for f in files if "restock" in f.lower() or "masuk" in f.lower()
        ]

        if not restock_files:
            # Try to find restock data in general files
            self.logger.warning(
                "No specific restock files found, searching in all files"
            )
            restock_files = files

        all_restock = []
        for file in restock_files:
            try:
                file_path = os.path.join(data_folder, file)
                df = self.data_utils.load_file(file_path)

                # Standardize columns for restock data
                expected_cols = [
                    "no",
                    "id_trx",
                    "tgl",
                    "id_item",
                    "nama_barang",
                    "kategori",
                    "jumlah",
                ]
                df = self.data_utils.standardize_columns(df, expected_cols)

                # Filter for restock transactions (masuk)
                restock_mask = (
                    df["kategori"].str.lower().str.contains("masuk", na=False)
                )
                restock = df[restock_mask].copy()

                if not restock.empty:
                    # Rename and clean quantity
                    restock = restock.rename(columns={"jumlah": "qty_restock"})
                    restock = self.data_utils.clean_numeric_column(
                        restock, "qty_restock"
                    )
                    restock = self.data_utils.process_dates(restock, "tgl")

                    all_restock.append(restock)
                    self.logger.info(
                        f"Loaded {len(restock)} restock records from {file}"
                    )

            except Exception as e:
                self.logger.error(f"Failed to load {file}: {str(e)}")
                continue

        if not all_restock:
            self.logger.warning("No restock data found, creating empty DataFrame")
            return pd.DataFrame(columns=["id_item", "tgl", "qty_restock"])

        combined_restock = pd.concat(all_restock, ignore_index=True)
        self.logger.info(f"Total restock records loaded: {len(combined_restock)}")

        return combined_restock


class StockPredictor:
    """Main Stock Prediction System - Simplified and Focused"""

    def __init__(self, base_path: Optional[str] = None):
        """Initialize the Stock Predictor"""
        self.base_path = Path(base_path) if base_path else Path(__file__).parent

        # Setup directory structure
        self.dirs = ConfigUtils.create_directory_structure(self.base_path)

        # Setup configuration
        self.config = ConfigUtils.get_default_config()

        # Setup logging
        log_file = self.dirs["logs"] / "stock_predictor.log"
        self.logger = LoggingUtils.setup_logger("StockPredictor", log_file)

        # Initialize components
        self.data_processor = StockDataProcessor(self.logger)
        self.feature_utils = FeatureUtils()
        self.model_utils = ModelUtils()
        self.validation_utils = ValidationUtils()

        # Model storage
        self.models = {}
        self.valid_products = {}
        self.product_mapping = {}

        self.logger.info("StockPredictor initialized successfully")
        self.logger.info(f"Base path: {self.base_path}")

    def train_sales_model(self, data_folder: str):
        """Train sales prediction model"""
        self.logger.info("TRAINING SALES PREDICTION MODEL")

        try:
            # Load sales data
            sales_data = self.data_processor.load_sales_data(data_folder)

            # Create product mapping
            if "nama_barang" in sales_data.columns:
                self.product_mapping = dict(
                    sales_data[["id_item", "nama_barang"]].drop_duplicates().values
                )

            # Create features
            sales_features = self.feature_utils.create_sales_features(sales_data)

            # Debug: Log available columns
            self.logger.info(f"Sales features columns: {list(sales_features.columns)}")
            self.logger.info(f"Sales features shape: {sales_features.shape}")

            # Prepare training data
            feature_cols = [
                "id_item",
                "prev_sales_1",
                "prev_sales_7",
                "prev_sales_30",
                "avg_sales_7",
                "avg_sales_30",
            ]

            # Remove rows with NaN in essential features
            self.logger.info("About to dropna with prev_sales_1...")
            sales_features = sales_features.dropna(subset=["prev_sales_1"])
            self.logger.info(f"After dropna shape: {sales_features.shape}")

            # Also drop NaN in other feature columns to prevent training errors
            feature_cols_to_check = [
                "prev_sales_7",
                "prev_sales_30",
                "avg_sales_7",
                "avg_sales_30",
            ]
            sales_features = sales_features.dropna(subset=feature_cols_to_check)
            self.logger.info(
                f"After dropping NaN in all features shape: {sales_features.shape}"
            )

            if sales_features.empty:
                raise ValueError("No valid training data after feature engineering")

            # Filter products with minimum samples
            product_counts = sales_features["id_item"].value_counts()
            valid_products = product_counts[
                product_counts >= self.config["MIN_SAMPLES"]
            ].index.tolist()

            self.logger.info(f"Valid products: {valid_products}")

            sales_features = sales_features[
                sales_features["id_item"].isin(valid_products)
            ]

            self.logger.info(f"Filtered sales features shape: {sales_features.shape}")
            self.logger.info(f"Columns after filtering: {list(sales_features.columns)}")

            # Debug: Check if feature_cols exist in dataframe
            missing_cols = [
                col for col in feature_cols if col not in sales_features.columns
            ]
            if missing_cols:
                self.logger.error(f"Missing columns: {missing_cols}")
                self.logger.error(f"Available columns: {list(sales_features.columns)}")
                raise ValueError(f"Missing required columns: {missing_cols}")

            try:
                X = sales_features[feature_cols]
            except KeyError as e:
                self.logger.error(f"KeyError when selecting feature columns: {e}")
                self.logger.error(f"Available columns: {list(sales_features.columns)}")
                self.logger.error(f"Requested feature_cols: {feature_cols}")
                raise

            try:
                y = sales_features["qty_sold"]
            except KeyError as e:
                self.logger.error(
                    f"KeyError when selecting target column 'qty_sold': {e}"
                )
                self.logger.error(f"Available columns: {list(sales_features.columns)}")
                raise

            self.logger.info(f"Training samples: {len(X)}")
            self.logger.info(f"Valid products: {len(valid_products)}")

            # Create and train model
            self.logger.info("About to create model pipeline...")
            sales_numeric_features = [
                "prev_sales_1",
                "prev_sales_7",
                "prev_sales_30",
                "avg_sales_7",
                "avg_sales_30",
            ]
            model = self._create_model_pipeline(numeric_features=sales_numeric_features)
            self.logger.info("About to fit model...")
            model.fit(X, y)
            self.logger.info("Model fitted successfully!")

            # Evaluate model
            y_pred = model.predict(X)
            metrics = self.model_utils.calculate_metrics(y.values, y_pred)

            self.logger.info("Sales Model Performance:")
            self.logger.info(f"  MAE: {metrics['mae']:.2f}")
            self.logger.info(f"  RMSE: {metrics['rmse']:.2f}")
            self.logger.info(f"  R²: {metrics['r2']:.3f}")

            # Save model
            self.models["sales"] = model
            self.valid_products["sales"] = [str(p) for p in valid_products]
            self._save_model("sales", model, metrics)

            self.logger.info("Sales model training completed successfully")

        except Exception as e:
            self.logger.error(f"Sales prediction error: {str(e)}")
            raise

    def predict_restock(
        self,
        product_id: str,
        # Laravel-style parameters
        avg_daily_sales: float = 0,
        sales_velocity: float = 0,
        transaction_count: int = 0,
        sales_volatility: float = 0,
        recent_total: float = 0,
        # Legacy parameters for backward compatibility
        current_sales: int = 0,
        prev_sales_7: int = 0,
        prev_restock_1: int = 0,
        days_since_restock: int = 0,
    ) -> Dict:
        """Predict restock quantity for a product"""
        start_time = time.time()

        # Map Laravel parameters to model features if legacy parameters not provided
        if current_sales == 0 and recent_total > 0:
            current_sales = int(recent_total)
        elif current_sales == 0 and avg_daily_sales > 0:
            current_sales = int(avg_daily_sales)

        if prev_sales_7 == 0 and avg_daily_sales > 0:
            prev_sales_7 = int(avg_daily_sales * 7)

        if prev_restock_1 == 0 and recent_total > 0:
            prev_restock_1 = int(recent_total)

        if days_since_restock == 0:
            # Estimate based on sales volatility (higher volatility = more recent restock needed)
            days_since_restock = max(1, int(30 - (sales_volatility * 10)))

        # Load model if not loaded
        if "restock" not in self.models:
            self.load_model("restock")

        try:
            # Validate product
            if not self.validation_utils.validate_product_id(
                product_id, self.valid_products.get("restock", [])
            ):
                # Generate fallback prediction
                fallback = self.model_utils.generate_fallback_prediction(
                    "restock",
                    current_stock=0,
                    avg_sales=prev_sales_7 / 7 if prev_sales_7 > 0 else 5,
                )

                return {
                    "prediction": fallback,
                    "product_id": product_id,
                    "prediction_type": "restock",
                    "execution_time_ms": round((time.time() - start_time) * 1000, 2),
                    "is_fallback": True,
                    "timestamp": datetime.now().isoformat(),
                }

            # Prepare input data
            input_data = pd.DataFrame(
                {
                    "id_item": [product_id],
                    "qty_sold": [current_sales],
                    "prev_sales_7": [prev_sales_7],
                    "prev_restock_1": [prev_restock_1],
                    "days_since_restock": [days_since_restock],
                }
            )

            # Make prediction
            prediction = self.models["restock"].predict(input_data)[0]
            prediction = max(0, round(prediction))

            return {
                "prediction": prediction,
                "product_id": product_id,
                "prediction_type": "restock",
                "execution_time_ms": round((time.time() - start_time) * 1000, 2),
                "is_fallback": False,
                "timestamp": datetime.now().isoformat(),
            }

        except Exception as e:
            self.logger.error(f"Restock prediction error: {str(e)}")
            raise

    def batch_predict(self, requests: List[Dict]) -> List[Dict]:
        """Make batch predictions for multiple products"""
        start_time = time.time()
        results = []

        self.logger.info(f"Processing {len(requests)} batch prediction requests")

        for i, request in enumerate(requests, 1):
            try:
                prediction_type = request.get("prediction_type", "sales")
                product_id = request.get("product_id")
                params = request.get("parameters", {})

                if prediction_type == "sales":
                    result = self.predict_sales(product_id, **params)
                elif prediction_type == "restock":
                    result = self.predict_restock(product_id, **params)
                else:
                    raise ValueError(f"Invalid prediction_type: {prediction_type}")

                result["batch_index"] = i
                results.append(result)

            except Exception as e:
                self.logger.error(f"Batch request {i} failed: {str(e)}")
                results.append(
                    {
                        "batch_index": i,
                        "error": str(e),
                        "product_id": request.get("product_id", "Unknown"),
                        "prediction_type": request.get("prediction_type", "Unknown"),
                        "timestamp": datetime.now().isoformat(),
                    }
                )

        total_time = (time.time() - start_time) * 1000

        # Add batch summary
        successful = len([r for r in results if "error" not in r])
        batch_summary = {
            "total_requests": len(requests),
            "successful": successful,
            "failed": len(requests) - successful,
            "success_rate": (successful / len(requests)) * 100,
            "total_time_ms": round(total_time, 2),
        }

        self.logger.info(f"Batch completed: {successful}/{len(requests)} successful")

        # Add summary to all successful results
        for result in results:
            if "error" not in result:
                result["batch_summary"] = batch_summary

        return results

    def train_all_models(self, data_folder: str):
        """Train both sales and restock models"""
        self.logger.info("TRAINING ALL MODELS")

        try:
            # Train sales model
            self.train_sales_model(data_folder)

            # Train restock model
            self.train_restock_model(data_folder)

            self.logger.info("All models trained successfully!")
            print("TRAINING_COMPLETED")

        except Exception as e:
            self.logger.error(f"Training failed: {str(e)}")
            print(f"TRAINING_FAILED: {str(e)}")
            raise

    def get_model_info(self) -> Dict:
        """Get information about trained models"""
        info = {
            "version": self.config["MODEL_VERSION"],
            "training_date": self.config["TRAINING_DATE"],
            "available_models": [],
            "valid_products": self.valid_products.copy(),
        }

        for model_type in ["sales", "restock"]:
            model_path = self.dirs["model"] / f"{model_type}_model.pkl"
            metadata_path = model_path.with_suffix(".json")

            if model_path.exists():
                model_info = {
                    "type": model_type,
                    "model_path": str(model_path),
                    "trained": True,
                }

                if metadata_path.exists():
                    with open(metadata_path, "r") as f:
                        metadata = json.load(f)
                    model_info.update(
                        {
                            "training_date": metadata.get("training_date"),
                            "metrics": metadata.get("metrics", {}),
                            "valid_products_count": len(
                                metadata.get("valid_products", [])
                            ),
                        }
                    )

                info["available_models"].append(model_info)

        return info

    def train_restock_model(self, data_folder: str):
        """Train restock prediction model"""
        self.logger.info("TRAINING RESTOCK PREDICTION MODEL")

        try:
            # Load both sales and restock data
            sales_data = self.data_processor.load_sales_data(data_folder)
            restock_data = self.data_processor.load_restock_data(data_folder)

            # Create features
            sales_features = self.feature_utils.create_sales_features(sales_data)
            restock_features = self.feature_utils.create_restock_features(restock_data)

            # Merge features
            combined_features = self.feature_utils.merge_sales_restock_features(
                sales_features, restock_features
            )

            # Prepare training data
            feature_cols = [
                "id_item",
                "qty_sold",
                "prev_sales_7",
                "prev_restock_1",
                "days_since_restock",
            ]

            # Remove rows with insufficient data
            combined_features = combined_features.dropna(
                subset=["qty_sold", "prev_sales_7"]
            )

            if combined_features.empty:
                raise ValueError("No valid training data after feature engineering")

            # Filter products with minimum samples
            product_counts = combined_features["id_item"].value_counts()
            valid_products = product_counts[
                product_counts >= self.config["MIN_SAMPLES"]
            ].index

            combined_features = combined_features[
                combined_features["id_item"].isin(valid_products)
            ]

            X = combined_features[feature_cols]
            y = combined_features["qty_restock"]

            self.logger.info(f"Training samples: {len(X)}")
            self.logger.info(f"Valid products: {len(valid_products)}")

            # Create and train model
            restock_numeric_features = [
                "qty_sold",
                "prev_sales_7",
                "prev_restock_1",
                "days_since_restock",
            ]
            model = self._create_model_pipeline(
                numeric_features=restock_numeric_features
            )
            model.fit(X, y)

            # Evaluate model
            y_pred = model.predict(X)
            metrics = self.model_utils.calculate_metrics(y.values, y_pred)

            self.logger.info("Restock Model Performance:")
            self.logger.info(f"  MAE: {metrics['mae']:.2f}")
            self.logger.info(f"  RMSE: {metrics['rmse']:.2f}")
            self.logger.info(f"  R²: {metrics['r2']:.3f}")

            # Save model
            self.models["restock"] = model
            self.valid_products["restock"] = list(valid_products.astype(str))
            self._save_model("restock", model, metrics)

            self.logger.info("Restock model training completed successfully")

        except Exception as e:
            self.logger.error(f"Restock model training failed: {str(e)}")
            raise

    def _create_model_pipeline(self, numeric_features=None) -> Pipeline:
        """Create model pipeline with preprocessing"""

        # Default numeric features if not specified
        if numeric_features is None:
            numeric_features = [
                "prev_sales_1",
                "prev_sales_7",
                "avg_sales_7",
                "avg_sales_30",
            ]

        preprocessor = ColumnTransformer(
            transformers=[
                ("cat", OneHotEncoder(handle_unknown="ignore"), ["id_item"]),
                ("num", StandardScaler(), numeric_features),
            ],
            remainder="drop",
        )

        model = Pipeline(
            [
                ("preprocessor", preprocessor),
                (
                    "regressor",
                    RandomForestRegressor(
                        n_estimators=self.config["N_ESTIMATORS"],
                        max_depth=self.config["MAX_DEPTH"],
                        random_state=self.config["RANDOM_STATE"],
                        n_jobs=-1,
                    ),
                ),
            ]
        )

        return model

    def _save_model(self, model_type: str, model: Pipeline, metrics: Dict):
        """Save model and metadata"""
        model_path = self.dirs["model"] / f"{model_type}_model.pkl"

        # Save model
        joblib.dump(model, model_path)

        # Save metadata
        metadata = {
            "model_type": model_type,
            "version": self.config["MODEL_VERSION"],
            "training_date": self.config["TRAINING_DATE"],
            "metrics": metrics,
            "valid_products": self.valid_products.get(model_type, []),
            "product_mapping": self.product_mapping,
        }

        metadata_path = model_path.with_suffix(".json")
        with open(metadata_path, "w") as f:
            json.dump(metadata, f, indent=2, default=str)

        self.logger.info(f"Model saved: {model_path}")

    def load_model(self, model_type: str) -> Pipeline:
        """Load trained model"""
        model_path = self.dirs["model"] / f"{model_type}_model.pkl"
        metadata_path = model_path.with_suffix(".json")

        if not model_path.exists():
            raise FileNotFoundError(f"Model not found: {model_path}")

        # Load model
        model = joblib.load(model_path)
        self.models[model_type] = model

        # Load metadata
        if metadata_path.exists():
            with open(metadata_path, "r") as f:
                metadata = json.load(f)
            self.valid_products[model_type] = metadata.get("valid_products", [])
            self.product_mapping = metadata.get("product_mapping", {})

        self.logger.info(f"Model loaded: {model_path}")
        return model

    def predict_sales(
        self,
        product_id: str,
        # Laravel-style parameters
        avg_daily_sales: float = 0,
        sales_velocity: float = 0,
        transaction_count: int = 0,
        sales_consistency: float = 0,
        recent_avg: float = 0,
        # Legacy parameters for backward compatibility
        prev_sales_1: int = 0,
        prev_sales_7: int = 0,
        avg_sales_7: float = 0,
        avg_sales_30: float = 0,
    ) -> Dict:
        """Predict sales for a product"""
        start_time = time.time()

        # Map Laravel parameters to model features if legacy parameters not provided
        if prev_sales_1 == 0 and avg_daily_sales > 0:
            prev_sales_1 = int(recent_avg) if recent_avg > 0 else int(avg_daily_sales)

        if prev_sales_7 == 0 and avg_daily_sales > 0:
            prev_sales_7 = int(avg_daily_sales * 7)

        # Calculate prev_sales_30 if not provided
        prev_sales_30 = int(avg_daily_sales * 30) if avg_daily_sales > 0 else 0

        if avg_sales_7 == 0:
            avg_sales_7 = avg_daily_sales if avg_daily_sales > 0 else recent_avg

        if avg_sales_30 == 0:
            avg_sales_30 = avg_daily_sales if avg_daily_sales > 0 else recent_avg

        # Load model if not loaded
        if "sales" not in self.models:
            self.load_model("sales")

        try:
            # Validate product
            if not self.validation_utils.validate_product_id(
                product_id, self.valid_products.get("sales", [])
            ):
                # Generate fallback prediction
                fallback = self.model_utils.generate_fallback_prediction(
                    "sales", prev_sales=prev_sales_1, avg_sales=avg_sales_7
                )

                return {
                    "prediction": fallback,
                    "product_id": product_id,
                    "prediction_type": "sales",
                    "execution_time_ms": round((time.time() - start_time) * 1000, 2),
                    "is_fallback": True,
                    "timestamp": datetime.now().isoformat(),
                }

            # Prepare input data
            input_data = pd.DataFrame(
                {
                    "id_item": [product_id],
                    "prev_sales_1": [prev_sales_1],
                    "prev_sales_7": [prev_sales_7],
                    "prev_sales_30": [prev_sales_30],
                    "avg_sales_7": [avg_sales_7],
                    "avg_sales_30": [avg_sales_30],
                }
            )

            # Make prediction
            prediction = self.models["sales"].predict(input_data)[0]
            prediction = max(0, round(prediction))

            return {
                "prediction": prediction,
                "product_id": product_id,
                "prediction_type": "sales",
                "execution_time_ms": round((time.time() - start_time) * 1000, 2),
                "is_fallback": False,
                "timestamp": datetime.now().isoformat(),
            }

        except Exception as e:
            self.logger.error(f"Sales prediction error: {str(e)}")
            fallback = self.model_utils.generate_fallback_prediction(
                "sales", prev_sales=prev_sales_1, avg_sales=avg_sales_7
            )
            return {
                "prediction": fallback,
                "product_id": product_id,
                "prediction_type": "sales",
                "execution_time_ms": round((time.time() - start_time) * 1000, 2),
                "is_fallback": True,
                "error": str(e),
                "timestamp": datetime.now().isoformat(),
            }

    def predict(self, product_id: str, prediction_type: str, **kwargs) -> Dict:
        """Generic predict method that routes to specific prediction methods"""
        if prediction_type.lower() == "sales":
            return self.predict_sales(product_id, **kwargs)
        elif prediction_type.lower() == "restock":
            return self.predict_restock(product_id, **kwargs)
        else:
            raise ValueError(f"Unknown prediction type: {prediction_type}")
