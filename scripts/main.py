# main.py - Main execution script for Stock Prediction System
"""
Main execution script for the Stock Prediction System

This script provides a professional interface to train models and make predictions
using argparse for better command-line handling.

Usage Examples:
    python main.py train --data-folder data
    python main.py predict --type sales --product PROD001 --avg-daily-sales 10 --sales-velocity 50
    python main.py predict --type restock --product PROD001 --avg-daily-sales 15 --sales-volatility 2.5
    python main.py info

Author: Stock Prediction System
Version: 5.0 - ArgParse Enhanced
"""

import sys
import json
import argparse
from pathlib import Path
from stock import StockPredictor


class StockPredictionAPI:
    """Professional API interface for the stock prediction system"""

    def __init__(self):
        self.predictor = StockPredictor()

    def train_models(self, data_folder: str = "data") -> dict:
        """Train both sales and restock models"""
        try:
            self.predictor.train_all_models(data_folder)
            return {
                "status": "success",
                "message": "Models trained successfully",
                "timestamp": self.predictor.config["TRAINING_DATE"],
            }
        except Exception as e:
            return {
                "status": "error",
                "message": f"Training failed: {str(e)}",
                "timestamp": self.predictor.config["TRAINING_DATE"],
            }

    def predict_sales(self, product_id: str, **kwargs) -> dict:
        """Predict sales for a product"""
        try:
            return self.predictor.predict(product_id, "sales", **kwargs)
        except Exception as e:
            return {
                "status": "error",
                "message": f"Sales prediction failed: {str(e)}",
                "product_id": product_id,
            }

    def predict_restock(self, product_id: str, **kwargs) -> dict:
        """Predict restock quantity for a product"""
        try:
            return self.predictor.predict(product_id, "restock", **kwargs)
        except Exception as e:
            return {
                "status": "error",
                "message": f"Restock prediction failed: {str(e)}",
                "product_id": product_id,
            }

    def get_model_status(self) -> dict:
        """Get status and information about trained models"""
        try:
            # Load model metadata
            sales_metadata_path = self.predictor.model_dir / "rf_sales_predictor.json"
            restock_metadata_path = (
                self.predictor.model_dir / "rf_restock_predictor.json"
            )

            models = []

            if sales_metadata_path.exists():
                with open(sales_metadata_path, "r") as f:
                    sales_meta = json.load(f)
                models.append(
                    {
                        "type": "sales",
                        "trained": True,
                        "valid_products_count": len(
                            sales_meta.get("valid_products", [])
                        ),
                        "metrics": sales_meta.get("performance_metrics", {}),
                    }
                )

            if restock_metadata_path.exists():
                with open(restock_metadata_path, "r") as f:
                    restock_meta = json.load(f)
                models.append(
                    {
                        "type": "restock",
                        "trained": True,
                        "valid_products_count": len(
                            restock_meta.get("valid_products", [])
                        ),
                        "metrics": restock_meta.get("performance_metrics", {}),
                    }
                )

            return {
                "status": "success",
                "version": self.predictor.config["MODEL_VERSION"],
                "training_date": self.predictor.config["TRAINING_DATE"],
                "available_models": models,
            }
        except Exception as e:
            return {"status": "error", "message": f"Failed to get model info: {str(e)}"}

    def batch_predict(self, requests: list) -> dict:
        """Process multiple prediction requests"""
        try:
            results = self.predictor.batch_predict(requests)
            return {
                "status": "success",
                "results": results,
                "total_requests": len(requests),
            }
        except Exception as e:
            return {
                "status": "error",
                "message": f"Batch prediction failed: {str(e)}",
                "total_requests": len(requests),
            }


def create_parser():
    """Create and configure argument parser"""
    parser = argparse.ArgumentParser(
        description="Stock Prediction System v5.0 - Sales and Restock Prediction",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  Training:
    python main.py train --data-folder data

  Sales Prediction:
    python main.py predict --type sales --product PROD001 \\
        --avg-daily-sales 10.5 --sales-velocity 8.2 \\
        --sales-consistency 1.5 --recent-avg 12.0 \\
        --transaction-count 45

  Restock Prediction:
    python main.py predict --type restock --product PROD001 \\
        --avg-daily-sales 15.3 --sales-velocity 9.1 \\
        --sales-volatility 2.5 --recent-total 105 \\
        --transaction-count 32

  Model Information:
    python main.py info
        """,
    )

    subparsers = parser.add_subparsers(dest="command", help="Available commands")

    # Train command
    train_parser = subparsers.add_parser("train", help="Train prediction models")
    train_parser.add_argument(
        "--data-folder",
        "-d",
        type=str,
        default="data",
        help="Path to data folder (default: data)",
    )

    # Predict command
    predict_parser = subparsers.add_parser("predict", help="Make predictions")
    predict_parser.add_argument(
        "--type",
        "-t",
        choices=["sales", "restock"],
        required=True,
        help="Type of prediction: sales or restock",
    )
    predict_parser.add_argument(
        "--product", "-p", type=str, required=True, help="Product ID for prediction"
    )

    # Sales prediction arguments
    sales_group = predict_parser.add_argument_group("Sales Prediction Parameters")
    sales_group.add_argument(
        "--avg-daily-sales",
        type=float,
        default=0.0,
        help="Average daily sales (default: 0.0)",
    )
    sales_group.add_argument(
        "--sales-velocity",
        type=float,
        default=0.0,
        help="Sales velocity metric (default: 0.0)",
    )
    sales_group.add_argument(
        "--sales-consistency",
        type=float,
        default=1.0,
        help="Sales consistency metric (default: 1.0)",
    )
    sales_group.add_argument(
        "--recent-avg",
        type=float,
        default=0.0,
        help="Recent average sales (default: 0.0)",
    )
    sales_group.add_argument(
        "--transaction-count",
        type=int,
        default=0,
        help="Total transaction count (default: 0)",
    )

    # Restock prediction arguments
    restock_group = predict_parser.add_argument_group("Restock Prediction Parameters")
    restock_group.add_argument(
        "--sales-volatility",
        type=float,
        default=1.0,
        help="Sales volatility metric (default: 1.0)",
    )
    restock_group.add_argument(
        "--recent-total",
        type=float,
        default=0.0,
        help="Recent total sales (default: 0.0)",
    )

    # Info command
    info_parser = subparsers.add_parser("info", help="Get model information")

    return parser


def handle_train_command(args, api):
    """Handle train command"""
    print(f"Starting model training with data folder: {args.data_folder}")

    if not Path(args.data_folder).exists():
        print(f"Error: Data folder '{args.data_folder}' not found")
        sys.exit(1)

    result = api.train_models(args.data_folder)

    if result["status"] == "success":
        print("SUCCESS: Models trained successfully")
        print("TRAINING_COMPLETED")
    else:
        print(f"ERROR: {result['message']}")
        print(f"TRAINING_FAILED: {result['message']}")
        sys.exit(1)


def handle_predict_command(args, api):
    """Handle predict command"""
    result = None
    try:
        if args.type == "sales":
            # Prepare sales prediction parameters
            sales_params = {
                "avg_daily_sales": args.avg_daily_sales,
                "sales_velocity": args.sales_velocity,
                "sales_consistency": args.sales_consistency,
                "recent_avg": args.recent_avg,
                "transaction_count": args.transaction_count,
            }

            print(f"Predicting sales for product: {args.product}")
            print(f"Parameters: {sales_params}")

            result = api.predict_sales(args.product, **sales_params)

        elif args.type == "restock":
            # Prepare restock prediction parameters
            restock_params = {
                "avg_daily_sales": args.avg_daily_sales,
                "sales_velocity": args.sales_velocity,
                "sales_volatility": args.sales_volatility,
                "recent_total": args.recent_total,
                "transaction_count": args.transaction_count,
            }

            print(f"Predicting restock for product: {args.product}")
            print(f"Parameters: {restock_params}")

            result = api.predict_restock(args.product, **restock_params)

        # Output results
        if result and "status" in result and result["status"] == "error":
            print(f"ERROR: {result['message']}")
            sys.exit(1)
        elif result:
            print(f"\nPrediction Results:")
            print(f"  Product ID: {result['product_id']}")
            print(f"  Prediction Type: {result['prediction_type']}")
            print(f"  Predicted Quantity: {result['prediction']} units")
            print(f"  Execution Time: {result['execution_time_ms']:.2f}ms")

            if result.get("is_fallback", False):
                print(
                    f"  Note: Fallback prediction (reason: {result.get('fallback_reason', 'unknown')})"
                )

            # For Laravel integration
            print(f"\nLARAVEL_OUTPUT:")
            print(f"PREDICTION_RESULT:{result['prediction']}")
            print(f"PREDICTION_FULL:{json.dumps(result)}")
        else:
            print("Error: No prediction result received")
            sys.exit(1)

    except Exception as e:
        print(f"Error: Prediction failed - {e}")
        sys.exit(1)


def handle_info_command(args, api):
    """Handle info command"""
    print("Getting model information...")
    result = api.get_model_status()

    if "status" in result and result["status"] == "error":
        print(f"Error: {result['message']}")
        sys.exit(1)
    else:
        print("\n" + "=" * 50)
        print("MODEL INFORMATION")
        print("=" * 50)
        print(f"Version: {result.get('version', 'Unknown')}")
        print(f"Training Date: {result.get('training_date', 'Unknown')}")

        models = result.get("available_models", [])
        if models:
            print(f"\nAvailable Models: {len(models)}")
            for model in models:
                print(f"\n  {model['type'].upper()} MODEL:")
                print(
                    f"    Status: {'Trained' if model.get('trained', False) else 'Not Trained'}"
                )

                if "valid_products_count" in model:
                    print(f"    Valid Products: {model['valid_products_count']}")

                if "metrics" in model and model["metrics"]:
                    metrics = model["metrics"]
                    print(f"    Performance Metrics:")
                    if "mae" in metrics:
                        print(f"      MAE: {metrics['mae']:.2f}")
                    if "r2" in metrics:
                        print(f"      R²: {metrics['r2']:.3f}")
                    if "rmse" in metrics:
                        print(f"      RMSE: {metrics['rmse']:.2f}")
        else:
            print("\nNo trained models found")

        print("\n" + "=" * 50)

        # Output JSON for machine consumption
        print(f"\nMODEL_INFO:{json.dumps(result)}")


def main():
    """Main function with argparse"""
    parser = create_parser()

    # Parse arguments
    args = parser.parse_args()

    # Handle no command case
    if not args.command:
        parser.print_help()
        sys.exit(1)

    # Initialize API
    try:
        api = StockPredictionAPI()
    except Exception as e:
        print(f"Error: Failed to initialize prediction system - {e}")
        sys.exit(1)

    # Route to appropriate handler
    if args.command == "train":
        handle_train_command(args, api)
    elif args.command == "predict":
        handle_predict_command(args, api)
    elif args.command == "info":
        handle_info_command(args, api)
    else:
        print(f"Error: Unknown command '{args.command}'")
        parser.print_help()
        sys.exit(1)


if __name__ == "__main__":
    main()
