#!/usr/bin/env python3
"""
Random Forest Model Training Script
Trains models using data exported from Laravel and saves them to scripts/models/
"""

import pandas as pd
import numpy as np
import json
import joblib
from datetime import datetime
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.metrics import mean_squared_error, r2_score, mean_absolute_error
from sklearn.preprocessing import LabelEncoder, StandardScaler
import warnings
from stock_logger import logger

warnings.filterwarnings("ignore")


class StockModelTrainer:
    def __init__(self):
        self.sales_model = None
        self.restock_model = None
        self.label_encoders = {}
        self.scalers = {}
        self.logger = logger
        self.model_info = {
            "sales": {},
            "restock": {},
            "training_date": datetime.now().isoformat(),
            "data_period": {},
        }

    def load_training_data(self):
        """Load training data from CSV files"""
        try:
            # Load sales data
            sales_df = pd.read_csv("data/sales_training_data.csv")
            self.logger.info(f"Loaded sales data: {len(sales_df)} records")

            # Load restock data
            restock_df = pd.read_csv("data/restock_training_data.csv")
            self.logger.info(f"Loaded restock data: {len(restock_df)} records")

            # Load product validation data
            with open("models/product_validation.json", "r") as f:
                validation_data = json.load(f)

            return sales_df, restock_df, validation_data

        except FileNotFoundError as e:
            self.logger.error(f"Training data file not found: {e}")
            return None, None, None
        except Exception as e:
            self.logger.error(f"Failed to load training data: {e}")
            return None, None, None

    def prepare_features(self, df, prediction_type):
        """Prepare features for training"""
        # Convert date to datetime
        df["date"] = pd.to_datetime(df["date"])

        # Create additional time-based features
        df["day_of_year"] = df["date"].dt.dayofyear
        df["week_of_year"] = df["date"].dt.isocalendar().week
        df["quarter"] = df["date"].dt.quarter

        # Aggregate data by item_id and month for better patterns
        monthly_data = (
            df.groupby(["item_id", "year", "month"])
            .agg(
                {
                    "quantity": ["sum", "mean", "count"],
                    "category": "first",
                    "product_name": "first",
                    "season": "first",
                    "is_weekend": "mean",
                }
            )
            .reset_index()
        )

        # Flatten column names
        monthly_data.columns = [
            "_".join(col).strip() if col[1] else col[0] for col in monthly_data.columns
        ]

        # Rename for clarity
        monthly_data = monthly_data.rename(
            columns={
                "quantity_sum": "total_quantity",
                "quantity_mean": "avg_quantity",
                "quantity_count": "transaction_count",
                "category_first": "category",
                "product_name_first": "product_name",
                "season_first": "season",
                "is_weekend_mean": "weekend_ratio",
            }
        )

        # Calculate rolling averages
        monthly_data = monthly_data.sort_values(["item_id", "year", "month"])
        monthly_data["rolling_avg_3m"] = (
            monthly_data.groupby("item_id")["total_quantity"]
            .rolling(window=3, min_periods=1)
            .mean()
            .reset_index(0, drop=True)
        )
        monthly_data["rolling_avg_6m"] = (
            monthly_data.groupby("item_id")["total_quantity"]
            .rolling(window=6, min_periods=1)
            .mean()
            .reset_index(0, drop=True)
        )

        # Calculate growth rate
        monthly_data["quantity_growth"] = (
            monthly_data.groupby("item_id")["total_quantity"].pct_change().fillna(0)
        )

        # Add lag features for better time series prediction
        monthly_data["lag_1"] = (
            monthly_data.groupby("item_id")["total_quantity"].shift(1).fillna(0)
        )
        monthly_data["lag_2"] = (
            monthly_data.groupby("item_id")["total_quantity"].shift(2).fillna(0)
        )

        # Add trend feature (linear trend over time)
        monthly_data["time_index"] = monthly_data.groupby("item_id").cumcount()

        # Add moving standard deviation for volatility
        monthly_data["rolling_std_3m"] = (
            monthly_data.groupby("item_id")["total_quantity"]
            .rolling(window=3, min_periods=1)
            .std()
            .reset_index(0, drop=True)
            .fillna(0)
        )

        # Encode categorical variables
        encoder_name = f"{prediction_type}_category_encoder"
        if encoder_name not in self.label_encoders:
            self.label_encoders[encoder_name] = LabelEncoder()
            monthly_data["category_encoded"] = self.label_encoders[
                encoder_name
            ].fit_transform(monthly_data["category"].astype(str))
        else:
            monthly_data["category_encoded"] = self.label_encoders[
                encoder_name
            ].transform(monthly_data["category"].astype(str))

        season_encoder_name = f"{prediction_type}_season_encoder"
        if season_encoder_name not in self.label_encoders:
            self.label_encoders[season_encoder_name] = LabelEncoder()
            monthly_data["season_encoded"] = self.label_encoders[
                season_encoder_name
            ].fit_transform(monthly_data["season"].astype(str))
        else:
            monthly_data["season_encoded"] = self.label_encoders[
                season_encoder_name
            ].transform(monthly_data["season"].astype(str))

        # Select features for training
        feature_columns = [
            "item_id",
            "month",
            "year",
            "avg_quantity",
            "transaction_count",
            "weekend_ratio",
            "rolling_avg_3m",
            "rolling_avg_6m",
            "quantity_growth",
            "lag_1",
            "lag_2",
            "time_index",
            "rolling_std_3m",
            "category_encoded",
            "season_encoded",
        ]

        # Create feature matrix
        X = monthly_data[feature_columns].fillna(0)
        # Use total_quantity as target (this is what we want to predict per month)
        y = monthly_data["total_quantity"]

        return X, y, monthly_data

    def train_model(self, X, y, prediction_type):
        """Train Random Forest model"""
        self.logger.info(f"Training {prediction_type} model...")
        self.logger.info(f"Features shape: {X.shape}")
        self.logger.info(f"Target shape: {y.shape}")

        # Split data with stratification for better representation
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.15, random_state=42, shuffle=True
        )

        # Scale features (optional for Random Forest, but can help)
        scaler_name = f"{prediction_type}_scaler"
        self.scalers[scaler_name] = StandardScaler()
        X_train_scaled = self.scalers[scaler_name].fit_transform(X_train)
        X_test_scaled = self.scalers[scaler_name].transform(X_test)

        # Train Random Forest with optimized hyperparameters
        # Increased complexity for better accuracy
        model = RandomForestRegressor(
            n_estimators=200,  # More trees for better ensemble
            max_depth=25,  # Deeper trees for capturing patterns
            min_samples_split=2,  # Allow finer splits
            min_samples_leaf=1,  # More detailed leaf nodes
            max_features=0.8,  # Use 80% of features per tree
            min_impurity_decrease=0.0,  # Allow all improvements
            bootstrap=True,  # Bootstrap sampling
            oob_score=True,  # Out-of-bag validation
            max_samples=0.85,  # Use 85% samples per tree
            random_state=42,
            n_jobs=-1,
            warm_start=False,
            verbose=0,
        )

        model.fit(X_train_scaled, y_train)

        # Make predictions
        y_pred_train = model.predict(X_train_scaled)
        y_pred_test = model.predict(X_test_scaled)

        # Calculate metrics
        train_r2 = r2_score(y_train, y_pred_train)
        test_r2 = r2_score(y_test, y_pred_test)
        train_rmse = np.sqrt(mean_squared_error(y_train, y_pred_train))
        test_rmse = np.sqrt(mean_squared_error(y_test, y_pred_test))
        train_mae = mean_absolute_error(y_train, y_pred_train)
        test_mae = mean_absolute_error(y_test, y_pred_test)

        # Get OOB score for additional validation
        oob_score = model.oob_score_ if hasattr(model, "oob_score_") else None

        # Cross-validation score with more folds for better validation
        cv_scores = cross_val_score(model, X_train_scaled, y_train, cv=10, scoring="r2")

        # Feature importance
        feature_importance = dict(zip(X.columns, model.feature_importances_))

        # Store model info
        self.model_info[prediction_type] = {
            "train_r2": train_r2,
            "test_r2": test_r2,
            "train_rmse": train_rmse,
            "test_rmse": test_rmse,
            "train_mae": train_mae,
            "test_mae": test_mae,
            "oob_score": oob_score,
            "cv_r2_mean": cv_scores.mean(),
            "cv_r2_std": cv_scores.std(),
            "feature_importance": feature_importance,
            "training_samples": len(X_train),
            "test_samples": len(X_test),
            "feature_columns": list(X.columns),
            "n_estimators": 200,
            "max_depth": 25,
        }

        self.logger.info(f"Training completed for {prediction_type}:")
        self.logger.info(f"  Train R²: {train_r2:.4f}")
        self.logger.info(f"  Test R²: {test_r2:.4f}")
        if oob_score:
            self.logger.info(f"  OOB Score: {oob_score:.4f}")
        self.logger.info(f"  ========= Train Metrics ({prediction_type}) =========")
        self.logger.info(f"  Train MAE: {train_mae:.4f}")
        self.logger.info(f"  Train RMSE: {train_rmse:.4f}")
        self.logger.info(f"  Test MAE: {test_mae:.4f}")
        self.logger.info(f"  Test RMSE: {test_rmse:.4f}")
        self.logger.info(f"  =================================")

        self.logger.info(
            f"  CV R² (mean ± std): {cv_scores.mean():.4f} ± {cv_scores.std():.4f}"
        )

        return model

    def save_models(self):
        """Save trained models and metadata"""
        self.logger.info("Saving models...")

        # Save models
        if self.sales_model:
            joblib.dump(self.sales_model, "models/sales_model.pkl")
            self.logger.info("✓ Sales model saved to models/sales_model.pkl")

        if self.restock_model:
            joblib.dump(self.restock_model, "models/restock_model.pkl")
            self.logger.info("✓ Restock model saved to models/restock_model.pkl")

        # Save label encoders
        joblib.dump(self.label_encoders, "models/label_encoders.pkl")
        self.logger.info("✓ Label encoders saved to models/label_encoders.pkl")

        # Save scalers
        joblib.dump(self.scalers, "models/scalers.pkl")
        self.logger.info("✓ Scalers saved to models/scalers.pkl")

        # Save model metadata
        with open("models/model_info.json", "w") as f:
            json.dump(self.model_info, f, indent=2)
        self.logger.info("✓ Model metadata saved to models/model_info.json")

    def train_all_models(self):
        """Train both sales and restock models"""
        self.logger.info("Starting model training...")

        # Load data
        sales_df, restock_df, validation_data = self.load_training_data()

        if sales_df is None or restock_df is None:
            self.logger.error("Cannot load training data")
            return False

        # Store data period info
        if validation_data:
            self.model_info["data_period"] = {
                "start_date": validation_data.get("data_period_start"),
                "end_date": validation_data.get("data_period_end"),
                "total_products": validation_data.get("total_products"),
                "products_with_sales_data": validation_data.get(
                    "products_with_sales_data"
                ),
                "products_with_restock_data": validation_data.get(
                    "products_with_restock_data"
                ),
            }

        try:
            # Train sales model
            if len(sales_df) > 10:  # Minimum data requirement
                X_sales, y_sales, _ = self.prepare_features(sales_df, "sales")
                self.sales_model = self.train_model(X_sales, y_sales, "sales")
            else:
                self.logger.warning("Not enough sales data for training")

            # Train restock model
            if len(restock_df) > 10:  # Minimum data requirement
                X_restock, y_restock, _ = self.prepare_features(restock_df, "restock")
                self.restock_model = self.train_model(X_restock, y_restock, "restock")
            else:
                self.logger.warning("Not enough restock data for training")

            # Save all models and metadata
            self.save_models()

            self.logger.info("TRAINING_COMPLETED")
            return True

        except Exception as e:
            self.logger.error(f"Training failed: {e}")
            return False


def main():
    """Main training function"""
    trainer = StockModelTrainer()
    success = trainer.train_all_models()

    if success:
        print("TRAINING_COMPLETED")
        trainer.logger.info("🎉 All models trained successfully!")
        return 0
    else:
        trainer.logger.error("❌ Training failed!")
        return 1


if __name__ == "__main__":
    main()
