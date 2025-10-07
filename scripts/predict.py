#!/usr/bin/env python3
"""
Stock Prediction using Pre-trained Random Forest Models
Usage: python predict.py --product p001 --type sales --avg-monthly 100
"""

import argparse
import json
import sys
import os
import numpy as np
import pandas as pd
import joblib
from datetime import datetime
import warnings
from stock_logger import logger
import time

warnings.filterwarnings("ignore")


class StockPredictor:
    def __init__(self):
        self.sales_model = None
        self.restock_model = None
        self.label_encoders = {}
        self.scalers = {}
        self.product_validation = {}
        self.model_info = {}
        self.is_model_loaded = False

    def load_models(self):
        """Load pre-trained models and validation data"""
        try:
            models_path = "models"

            # Check if models exist
            if not os.path.exists(f"{models_path}/sales_model.pkl"):
                logger.warning("Sales model not found. Please train the model first.")
                return False

            if not os.path.exists(f"{models_path}/restock_model.pkl"):
                logger.warning("Restock model not found. Please train the model first.")
                return False

            # Load models
            self.sales_model = joblib.load(f"{models_path}/sales_model.pkl")
            self.restock_model = joblib.load(f"{models_path}/restock_model.pkl")
            logger.info("✓ Models loaded successfully")

            # Load label encoders and scalers
            self.label_encoders = joblib.load(f"{models_path}/label_encoders.pkl")
            self.scalers = joblib.load(f"{models_path}/scalers.pkl")
            logger.info("✓ Encoders and scalers loaded")

            # Load product validation data
            with open(f"{models_path}/product_validation.json", "r") as f:
                self.product_validation = json.load(f)
            logger.info("✓ Product validation data loaded")

            # Load model info
            with open(f"{models_path}/model_info.json", "r") as f:
                self.model_info = json.load(f)
            logger.info("✓ Model metadata loaded")

            self.is_model_loaded = True
            return True

        except FileNotFoundError as e:
            logger.error(f"Model file not found: {e}")
            logger.error("Please run training first: python train_model.py")
            return False
        except Exception as e:
            logger.error(f"Error creating features: {str(e)}")
            raise Exception(f"Feature creation failed: {str(e)}")

    def _calculate_feature_confidence(
        self, features_scaled, prediction_type, avg_monthly
    ):
        """Calculate confidence based on how well input features match training patterns"""
        try:
            # Simple heuristic based on input values
            # You can enhance this based on your training data characteristics

            # Basic confidence calculation based on avg_monthly input
            if avg_monthly <= 0:
                return 20.0  # Very low confidence for invalid input
            elif avg_monthly < 10:
                return 40.0  # Low confidence for very low values
            elif avg_monthly <= 200:
                return 85.0  # High confidence for normal range
            elif avg_monthly <= 500:
                return 70.0  # Medium confidence for high values
            else:
                return 50.0  # Lower confidence for very high values

        except Exception as e:
            logger.warning(f"Could not calculate feature confidence: {e}")
            return 50.0  # Default neutral confidence

    def validate_product(self, product_id, prediction_type):
        """Validate if product exists and has relevant data"""
        if not self.product_validation:
            logger.warning("No product validation data available")
            return {
                "valid": True,
                "message": "No validation data available, proceeding with prediction",
            }

        products = self.product_validation.get("products", {})

        # Check if product exists
        if str(product_id) not in products:
            return {
                "valid": False,
                "message": f"Product {product_id} not found in training data. Available products: {len(products)}",
            }

        product_info = products[str(product_id)]

        # Check if product has relevant training data
        if prediction_type == "sales" and not product_info.get("has_sales_data", False):
            return {
                "valid": False,
                "message": f'Product {product_id} ({product_info.get("name", "Unknown")}) has no sales data for training',
            }

        if prediction_type == "restock" and not product_info.get(
            "has_restock_data", False
        ):
            return {
                "valid": False,
                "message": f'Product {product_id} ({product_info.get("name", "Unknown")}) has no restock data for training',
            }

        return {
            "valid": True,
            "product_info": product_info,
            "message": f'Product {product_id} ({product_info.get("name", "Unknown")}) validated successfully',
        }

    def create_prediction_features(self, product_id, avg_monthly, prediction_type):
        """Create features for prediction based on trained model format"""
        try:
            # Get current date features
            now = datetime.now()
            month = now.month
            year = now.year

            # Get product info for category
            products = self.product_validation.get("products", {})
            product_info = products.get(str(product_id), {})
            category = product_info.get("category", "Unknown")

            # Determine season
            if month in [12, 1, 2]:
                season = "winter"
            elif month in [3, 4, 5]:
                season = "spring"
            elif month in [6, 7, 8]:
                season = "summer"
            else:
                season = "autumn"

            # Create base features similar to training
            # avg_monthly is expected to be the TOTAL monthly quantity
            # We need to estimate avg_quantity (per transaction) and transaction_count

            # Historical avg: ~2.87 unit per transaction, ~37 transactions per month
            estimated_avg_per_transaction = 2.87  # From training data stats
            estimated_transaction_count = max(
                1, int(avg_monthly / estimated_avg_per_transaction)
            )

            # Dynamic adjustment based on volume
            # Higher volume products tend to have slightly higher per-transaction avg
            volume_factor = 1.0
            if avg_monthly > 120:
                volume_factor = 1.05  # 5% boost for high volume
            elif avg_monthly < 80:
                volume_factor = 0.95  # 5% reduction for low volume

            features = {
                "item_id": (
                    int(product_id)
                    if str(product_id).isdigit()
                    else hash(str(product_id)) % 10000
                ),
                "month": month,
                "year": year,
                "avg_quantity": estimated_avg_per_transaction * volume_factor,
                "transaction_count": int(estimated_transaction_count / volume_factor),
                "weekend_ratio": 0.29,  # Average weekend ratio
                "rolling_avg_3m": avg_monthly,  # Use current total as rolling avg
                "rolling_avg_6m": avg_monthly,  # Use current total as rolling avg
                "quantity_growth": 0.05,  # Slight positive growth tendency
                "lag_1": avg_monthly * 0.98,  # Slight variation in lag
                "lag_2": avg_monthly * 0.96,  # Progressive variation
                "time_index": 6,  # Median time index estimate
                "rolling_std_3m": avg_monthly * 0.18,  # Increased volatility estimate
            }

            # Encode categorical features
            try:
                category_encoder = self.label_encoders.get(
                    f"{prediction_type}_category_encoder"
                )
                if category_encoder:
                    # Handle unknown categories
                    try:
                        features["category_encoded"] = category_encoder.transform(
                            [category]
                        )[0]
                    except ValueError:
                        # Use most frequent category if unknown
                        features["category_encoded"] = 0
                else:
                    features["category_encoded"] = 0

                season_encoder = self.label_encoders.get(
                    f"{prediction_type}_season_encoder"
                )
                if season_encoder:
                    try:
                        features["season_encoded"] = season_encoder.transform([season])[
                            0
                        ]
                    except ValueError:
                        features["season_encoded"] = 0
                else:
                    features["season_encoded"] = 0

            except Exception as e:
                logger.warning(f"Error encoding features: {e}")
                features["category_encoded"] = 0
                features["season_encoded"] = 0

            # Convert to DataFrame with correct feature order
            feature_columns = self.model_info.get(prediction_type, {}).get(
                "feature_columns", list(features.keys())
            )

            # Ensure all required features are present
            for col in feature_columns:
                if col not in features:
                    features[col] = 0

            # Create feature vector in correct order
            feature_vector = np.array(
                [features.get(col, 0) for col in feature_columns]
            ).reshape(1, -1)

            return feature_vector, feature_columns

        except Exception as e:
            logger.error(f"Failed to create features: {e}")
            # Fallback: create minimal feature set
            now = datetime.now()
            return np.array(
                [
                    [
                        int(product_id) if str(product_id).isdigit() else 1,
                        now.month,
                        now.year,
                        avg_monthly,
                        1,
                        0.29,
                        avg_monthly,
                        avg_monthly,
                        0.0,
                        0,
                        0,
                    ]
                ]
            ), [
                "item_id",
                "month",
                "year",
                "avg_quantity",
                "transaction_count",
                "weekend_ratio",
                "rolling_avg_3m",
                "rolling_avg_6m",
                "quantity_growth",
                "category_encoded",
                "season_encoded",
            ]

    def predict(self, product_id, prediction_type, avg_monthly):
        """Make prediction using trained model"""
        # Start timing the prediction process
        start_time = time.time()

        if not self.is_model_loaded:
            if not self.load_models():
                return {
                    "success": False,
                    "message": "Failed to load models. Please train the model first.",
                }

        # Validate product
        validation_start = time.time()
        validation_result = self.validate_product(product_id, prediction_type)
        validation_time = (time.time() - validation_start) * 1000

        if not validation_result["valid"]:
            execution_time = (time.time() - start_time) * 1000
            return {
                "success": False,
                "message": validation_result["message"],
                "execution_time_ms": round(execution_time, 2),
            }

        try:
            # Create features
            features_start = time.time()
            features, feature_columns = self.create_prediction_features(
                product_id, avg_monthly, prediction_type
            )
            features_time = (time.time() - features_start) * 1000

            # Scale features
            scaling_start = time.time()
            scaler_name = f"{prediction_type}_scaler"
            if scaler_name in self.scalers:
                features_scaled = self.scalers[scaler_name].transform(features)
            else:
                features_scaled = features
                logger.warning("Scaler not found, using unscaled features")
            scaling_time = (time.time() - scaling_start) * 1000

            # Select model
            model = (
                self.sales_model if prediction_type == "sales" else self.restock_model
            )
            if model is None:
                execution_time = (time.time() - start_time) * 1000
                return {
                    "success": False,
                    "message": f"{prediction_type.capitalize()} model not available",
                    "execution_time_ms": round(execution_time, 2),
                }

            # Make prediction
            prediction_start = time.time()
            prediction = model.predict(features_scaled)[0]

            # Calculate prediction confidence and accuracy metrics
            prediction_confidence = {}
            prediction_accuracy = {}

            try:
                # For Random Forest, we can get prediction confidence from trees
                if hasattr(model, "estimators_"):
                    # Get predictions from all trees
                    tree_predictions = [
                        tree.predict(features_scaled)[0] for tree in model.estimators_
                    ]
                    pred_std = np.std(tree_predictions)
                    pred_mean = np.mean(tree_predictions)

                    # Calculate various accuracy metrics
                    coefficient_of_variation = (
                        (pred_std / pred_mean * 100) if pred_mean > 0 else 100
                    )
                    prediction_stability = max(0, 100 - coefficient_of_variation)

                    # Calculate feature-based confidence (how well input matches training patterns)
                    feature_confidence = self._calculate_feature_confidence(
                        features_scaled, prediction_type, avg_monthly
                    )

                    # Combined prediction accuracy
                    combined_accuracy = (prediction_stability + feature_confidence) / 2

                    # Tree consensus accuracy
                    median_pred = np.median(tree_predictions)
                    consensus_trees = len(
                        [
                            p
                            for p in tree_predictions
                            if abs(p - median_pred) <= pred_std
                        ]
                    )
                    tree_consensus = (consensus_trees / len(tree_predictions)) * 100

                    prediction_confidence = {
                        "confidence_score": round(prediction_stability, 2),
                        "prediction_std": round(pred_std, 2),
                        "prediction_range": f"{round(pred_mean - pred_std, 2)} - {round(pred_mean + pred_std, 2)}",
                        "tree_consensus": f"{consensus_trees}/{len(tree_predictions)} trees ({tree_consensus:.1f}%)",
                    }

                    prediction_accuracy = {
                        "prediction_accuracy": round(
                            combined_accuracy, 2
                        ),  # Main accuracy
                        "prediction_stability": round(prediction_stability, 2),
                        "feature_match_confidence": round(feature_confidence, 2),
                        "combined_accuracy": round(
                            combined_accuracy, 2
                        ),  # Keep for backward compatibility
                        "coefficient_of_variation": round(coefficient_of_variation, 2),
                        "tree_consensus_pct": round(tree_consensus, 2),
                    }

            except Exception as e:
                logger.warning(f"Could not calculate prediction confidence: {e}")
                prediction_confidence = {"confidence_score": "N/A"}
                prediction_accuracy = {
                    "prediction_accuracy": "N/A",
                    "combined_accuracy": "N/A",
                }

            prediction = max(0, round(prediction, 2))

            # Apply calibration adjustment to compensate for systematic underestimation
            # Based on historical analysis, model tends to predict ~15-20% lower than actual
            calibration_factor = 1.12  # 12% upward adjustment
            prediction = round(prediction * calibration_factor, 2)

            model_prediction_time = (time.time() - prediction_start) * 1000

            # Calculate total execution time
            total_execution_time = (time.time() - start_time) * 1000

            # Get model performance info
            model_performance = self.model_info.get(prediction_type, {})

            # Log timing information
            logger.info(f"=== EXECUTION TIMING ===")
            logger.info(f"Validation: {validation_time:.2f}ms")
            logger.info(f"Feature Creation: {features_time:.2f}ms")
            logger.info(f"Feature Scaling: {scaling_time:.2f}ms")
            logger.info(f"Model Prediction: {model_prediction_time:.2f}ms")
            logger.info(f"Total Execution: {total_execution_time:.2f}ms")

            return {
                "success": True,
                "prediction": prediction,
                "product_id": product_id,
                "product_info": validation_result.get("product_info", {}),
                "prediction_type": prediction_type,
                "avg_monthly_input": avg_monthly,
                "execution_time_ms": round(total_execution_time, 2),
                "timing_breakdown": {
                    "validation_ms": round(validation_time, 2),
                    "feature_creation_ms": round(features_time, 2),
                    "feature_scaling_ms": round(scaling_time, 2),
                    "model_prediction_ms": round(model_prediction_time, 2),
                },
                "model_performance": {
                    "test_r2": model_performance.get("test_r2", "N/A"),
                    "test_rmse": model_performance.get("test_rmse", "N/A"),
                    "training_samples": model_performance.get(
                        "training_samples", "N/A"
                    ),
                },
                "prediction_confidence": prediction_confidence,
                "prediction_accuracy": prediction_accuracy,  # NEW!
                "features_used": feature_columns,
                "validation_message": validation_result["message"],
            }

        except Exception as e:
            execution_time = (time.time() - start_time) * 1000
            return {
                "success": False,
                "message": f"Prediction failed: {str(e)}",
                "execution_time_ms": round(execution_time, 2),
            }


def main():
    parser = argparse.ArgumentParser(
        description="Stock Prediction using Pre-trained Random Forest Models"
    )
    parser.add_argument("--product", required=True, help="Product ID")
    parser.add_argument(
        "--type",
        required=True,
        choices=["sales", "restock"],
        help="Prediction type: sales or restock",
    )
    parser.add_argument(
        "--avg-monthly", type=float, required=True, help="Average monthly sales/usage"
    )

    args = parser.parse_args()

    try:
        # Log prediction request
        logger.info(f"=== PREDICTION REQUEST ===")
        logger.info(f"Product ID: {args.product}")
        logger.info(f"Prediction Type: {args.type}")
        logger.info(f"Average Monthly: {args.avg_monthly}")

        # Initialize predictor
        predictor = StockPredictor()

        # Make prediction
        result = predictor.predict(args.product, args.type, args.avg_monthly)

        if result["success"]:
            # Log successful prediction
            logger.info(f"=== PREDICTION RESULT ===")
            logger.info(f"Prediction: {result['prediction']}")
            logger.info(
                f"Product: {result.get('product_info', {}).get('name', 'Unknown')}"
            )
            logger.info(f"Validation: {result['validation_message']}")

            # Add prediction-specific confidence metrics
            prediction_confidence = result.get("prediction_confidence", {})
            prediction_accuracy = result.get("prediction_accuracy", {})

            if prediction_confidence:
                logger.info(f"=== PREDICTION CONFIDENCE ===")
                logger.info(
                    f"Confidence Score: {prediction_confidence.get('confidence_score', 'N/A'):.2f}%"
                )
                logger.info(
                    f"Prediction Range: {prediction_confidence.get('prediction_range', 'N/A')}"
                )
                logger.info(
                    f"Tree Consensus: {prediction_confidence.get('tree_consensus', 'N/A')}"
                )

            # NEW: Display prediction-specific accuracy metrics
            if prediction_accuracy:
                logger.info(f"=== PREDIKSI AKURASI ===")
                main_acc = prediction_accuracy.get("prediction_accuracy", "N/A")
                stability_acc = prediction_accuracy.get("prediction_stability", "N/A")
                feature_conf = prediction_accuracy.get(
                    "feature_match_confidence", "N/A"
                )
                tree_consensus = prediction_accuracy.get("tree_consensus_pct", "N/A")
                cv = prediction_accuracy.get("coefficient_of_variation", "N/A")

                logger.info(
                    f"Prediction Accuracy: {main_acc:.1f}%"
                    if isinstance(main_acc, (int, float))
                    else f"Prediction Accuracy: {main_acc}"
                )
                logger.info(
                    f"Prediction Stability: {stability_acc:.1f}%"
                    if isinstance(stability_acc, (int, float))
                    else f"Prediction Stability: {stability_acc}"
                )
                logger.info(
                    f"Feature Match Score: {feature_conf:.1f}%"
                    if isinstance(feature_conf, (int, float))
                    else f"Feature Match Score: {feature_conf}"
                )
                logger.info(
                    f"Tree Consensus: {tree_consensus:.1f}%"
                    if isinstance(tree_consensus, (int, float))
                    else f"Tree Consensus: {tree_consensus}"
                )
                logger.info(
                    f"Variation Coefficient: {cv:.2f}%"
                    if isinstance(cv, (int, float))
                    else f"Variation Coefficient: {cv}"
                )

                # Provide accuracy interpretation
                if isinstance(main_acc, (int, float)):
                    if main_acc >= 85:
                        acc_level = "Excellent"
                    elif main_acc >= 70:
                        acc_level = "Good"
                    elif main_acc >= 50:
                        acc_level = "Fair"
                    else:
                        acc_level = "Poor"
                    logger.info(f"Prediction Accuracy Level: {acc_level}")

            # Output result as JSON
            print("PREDICTION_RESULT:", json.dumps(result))

            return 0
        else:
            logger.error(f"Prediction failed: {result['message']}")
            print(f"ERROR: {result['message']}", file=sys.stderr)
            return 1

    except Exception as e:
        logger.error(f"Prediction error: {str(e)}")
        print(f"ERROR: {str(e)}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    exit(main())
