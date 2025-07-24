#!/usr/bin/env python3
"""
Inventory Analysis Script
Performs association rule mining (Apriori) and demand prediction (Random Forest)
"""

import pandas as pd
import numpy as np
import json
import sys
import os
from datetime import datetime, timedelta
from typing import Dict, List, Tuple, Any
import warnings

warnings.filterwarnings("ignore")

# ML libraries
try:
    from mlxtend.frequent_patterns import apriori, association_rules
    from mlxtend.preprocessing import TransactionEncoder
    from sklearn.ensemble import RandomForestRegressor
    from sklearn.model_selection import train_test_split
    from sklearn.metrics import mean_absolute_error, mean_squared_error
    from sklearn.preprocessing import StandardScaler
except ImportError as e:
    print(f"Error importing required libraries: {e}")
    print("Please install required packages:")
    print("pip install mlxtend scikit-learn pandas numpy")
    sys.exit(1)


class InventoryAnalyzer:
    def __init__(self, data_path: str, output_path: str):
        self.data_path = data_path
        self.output_path = output_path
        self.transactions_df = None
        self.association_rules_df = None
        self.predictions_df = None
        self.analysis_summary = {}

    def load_data(self) -> bool:
        """Load transaction data from CSV"""
        try:
            if not os.path.exists(self.data_path):
                print(f"Error: Data file not found at {self.data_path}")
                return False

            self.transactions_df = pd.read_csv(self.data_path)
            print(f"Loaded {len(self.transactions_df)} transactions")

            # Validate required columns
            required_columns = ["item_code", "item_name", "outgoing_date", "quantity"]
            missing_columns = [
                col
                for col in required_columns
                if col not in self.transactions_df.columns
            ]
            if missing_columns:
                print(f"Error: Missing required columns: {missing_columns}")
                return False

            # Convert date column
            self.transactions_df["outgoing_date"] = pd.to_datetime(
                self.transactions_df["outgoing_date"]
            )
            return True

        except Exception as e:
            print(f"Error loading data: {e}")
            return False

    def perform_apriori_analysis(
        self, min_support: float = 0.01, min_confidence: float = 0.5
    ) -> bool:
        """Perform Apriori association rule mining"""
        try:
            if self.transactions_df is None or len(self.transactions_df) == 0:
                print("No transaction data available for analysis")
                return False

            print("Performing Apriori association analysis...")

            # Group transactions by date and customer to create transaction baskets
            # If customer column doesn't exist, group by date only
            if "customer" in self.transactions_df.columns:
                basket_df = (
                    self.transactions_df.groupby(["outgoing_date", "customer"])[
                        "item_code"
                    ]
                    .apply(list)
                    .reset_index()
                )
            else:
                basket_df = (
                    self.transactions_df.groupby("outgoing_date")["item_code"]
                    .apply(list)
                    .reset_index()
                )

            # Prepare transaction data for apriori
            transactions = basket_df["item_code"].tolist()

            # Remove transactions with only one item
            transactions = [
                transaction for transaction in transactions if len(transaction) > 1
            ]

            if len(transactions) < 10:
                print("Insufficient multi-item transactions for meaningful analysis")
                # Create empty results
                self.association_rules_df = pd.DataFrame(
                    columns=[
                        "antecedents",
                        "consequents",
                        "support",
                        "confidence",
                        "lift",
                    ]
                )
                return True

            # Encode transactions
            te = TransactionEncoder()
            te_ary = te.fit(transactions).transform(transactions)
            df_encoded = pd.DataFrame(te_ary, columns=te.columns_)

            # Find frequent itemsets
            frequent_itemsets = apriori(
                df_encoded, min_support=min_support, use_colnames=True
            )

            if len(frequent_itemsets) == 0:
                print("No frequent itemsets found with current support threshold")
                # Lower the threshold and try again
                frequent_itemsets = apriori(
                    df_encoded, min_support=0.005, use_colnames=True
                )

            if len(frequent_itemsets) == 0:
                print("Still no frequent itemsets found - creating empty results")
                self.association_rules_df = pd.DataFrame(
                    columns=[
                        "antecedents",
                        "consequents",
                        "support",
                        "confidence",
                        "lift",
                    ]
                )
                return True

            # Generate association rules
            rules = association_rules(
                frequent_itemsets, metric="confidence", min_threshold=min_confidence
            )

            if len(rules) == 0:
                print("No association rules found with current confidence threshold")
                # Lower the threshold and try again
                rules = association_rules(
                    frequent_itemsets, metric="confidence", min_threshold=0.3
                )

            if len(rules) == 0:
                print("Still no rules found - creating empty results")
                self.association_rules_df = pd.DataFrame(
                    columns=[
                        "antecedents",
                        "consequents",
                        "support",
                        "confidence",
                        "lift",
                    ]
                )
                return True

            # Sort by lift and confidence
            rules = rules.sort_values(["lift", "confidence"], ascending=False)

            # Convert frozensets to lists for JSON serialization
            rules["antecedents"] = rules["antecedents"].apply(lambda x: list(x))
            rules["consequents"] = rules["consequents"].apply(lambda x: list(x))

            self.association_rules_df = rules[
                ["antecedents", "consequents", "support", "confidence", "lift"]
            ].copy()

            print(f"Generated {len(self.association_rules_df)} association rules")
            return True

        except Exception as e:
            print(f"Error in Apriori analysis: {e}")
            return False

    def perform_demand_prediction(self, prediction_days: int = 30) -> bool:
        """Perform demand prediction using Random Forest"""
        try:
            if self.transactions_df is None or len(self.transactions_df) == 0:
                print("No transaction data available for prediction")
                return False

            print("Performing demand prediction...")

            # Aggregate daily demand per item
            daily_demand = (
                self.transactions_df.groupby(["outgoing_date", "item_code"])
                .agg({"quantity": "sum", "item_name": "first"})
                .reset_index()
            )

            predictions_list = []

            # Process each item separately
            for item_code in daily_demand["item_code"].unique():
                item_data = daily_demand[daily_demand["item_code"] == item_code].copy()
                item_name = item_data["item_name"].iloc[0]

                # Skip items with insufficient data
                if len(item_data) < 7:
                    continue

                # Sort by date
                item_data = item_data.sort_values("outgoing_date")

                # Create features
                item_data["day_of_week"] = item_data["outgoing_date"].dt.dayofweek
                item_data["month"] = item_data["outgoing_date"].dt.month
                item_data["day_of_month"] = item_data["outgoing_date"].dt.day

                # Rolling averages
                item_data["rolling_7_avg"] = (
                    item_data["quantity"].rolling(window=7, min_periods=1).mean()
                )
                item_data["rolling_30_avg"] = (
                    item_data["quantity"].rolling(window=30, min_periods=1).mean()
                )

                # Lag features
                item_data["lag_1"] = item_data["quantity"].shift(1)
                item_data["lag_7"] = item_data["quantity"].shift(7)

                # Fill NaN values
                item_data = item_data.fillna(0)

                # Prepare features and target
                feature_columns = [
                    "day_of_week",
                    "month",
                    "day_of_month",
                    "rolling_7_avg",
                    "rolling_30_avg",
                    "lag_1",
                    "lag_7",
                ]
                X = item_data[feature_columns]
                y = item_data["quantity"]

                # Skip if insufficient data for training
                if len(X) < 5:
                    continue

                try:
                    # Train Random Forest model
                    if len(X) > 10:
                        X_train, X_test, y_train, y_test = train_test_split(
                            X, y, test_size=0.2, random_state=42
                        )
                    else:
                        X_train, X_test, y_train, y_test = X, X, y, y

                    rf_model = RandomForestRegressor(
                        n_estimators=50, random_state=42, max_depth=5
                    )
                    rf_model.fit(X_train, y_train)

                    # Make predictions
                    y_pred = rf_model.predict(X_test)

                    # Calculate prediction confidence (simplified)
                    mae = mean_absolute_error(y_test, y_pred) if len(y_test) > 1 else 0
                    avg_demand = y.mean()
                    confidence = max(0, min(100, (1 - mae / max(avg_demand, 1)) * 100))

                    # Predict future demand
                    last_row = X.iloc[-1:].copy()
                    future_prediction = rf_model.predict(last_row)[0]
                    future_prediction = max(0, int(round(future_prediction)))

                    # Feature importance
                    feature_importance = dict(
                        zip(feature_columns, rf_model.feature_importances_)
                    )

                    predictions_list.append(
                        {
                            "item_code": item_code,
                            "item_name": item_name,
                            "predicted_demand": future_prediction,
                            "prediction_confidence": round(confidence, 2),
                            "prediction_period_start": (datetime.now()).strftime(
                                "%Y-%m-%d"
                            ),
                            "prediction_period_end": (
                                datetime.now() + timedelta(days=prediction_days)
                            ).strftime("%Y-%m-%d"),
                            "feature_importance": feature_importance,
                        }
                    )

                except Exception as e:
                    print(f"Error predicting for item {item_code}: {e}")
                    continue

            if predictions_list:
                self.predictions_df = pd.DataFrame(predictions_list)
                print(f"Generated predictions for {len(self.predictions_df)} items")
            else:
                print("No predictions could be generated")
                self.predictions_df = pd.DataFrame(
                    columns=[
                        "item_code",
                        "item_name",
                        "predicted_demand",
                        "prediction_confidence",
                        "prediction_period_start",
                        "prediction_period_end",
                        "feature_importance",
                    ]
                )

            return True

        except Exception as e:
            print(f"Error in demand prediction: {e}")
            return False

    def save_results(self) -> bool:
        """Save analysis results to CSV files"""
        try:
            os.makedirs(self.output_path, exist_ok=True)

            # Save association rules
            recommendations_file = os.path.join(self.output_path, "recommendations.csv")
            if self.association_rules_df is not None:
                # Convert lists to strings for CSV
                rules_to_save = self.association_rules_df.copy()
                rules_to_save["antecedents"] = rules_to_save["antecedents"].apply(
                    lambda x: ",".join(x)
                )
                rules_to_save["consequents"] = rules_to_save["consequents"].apply(
                    lambda x: ",".join(x)
                )
                rules_to_save.to_csv(recommendations_file, index=False)
                print(
                    f"Saved {len(rules_to_save)} recommendations to {recommendations_file}"
                )
            else:
                # Create empty file
                pd.DataFrame(
                    columns=[
                        "antecedents",
                        "consequents",
                        "support",
                        "confidence",
                        "lift",
                    ]
                ).to_csv(recommendations_file, index=False)

            # Save predictions
            predictions_file = os.path.join(self.output_path, "predictions.csv")
            if self.predictions_df is not None:
                # Convert feature importance dict to JSON string
                predictions_to_save = self.predictions_df.copy()
                predictions_to_save["feature_importance"] = predictions_to_save[
                    "feature_importance"
                ].apply(json.dumps)
                predictions_to_save.to_csv(predictions_file, index=False)
                print(
                    f"Saved {len(predictions_to_save)} predictions to {predictions_file}"
                )
            else:
                # Create empty file
                pd.DataFrame(
                    columns=[
                        "item_code",
                        "item_name",
                        "predicted_demand",
                        "prediction_confidence",
                        "prediction_period_start",
                        "prediction_period_end",
                        "feature_importance",
                    ]
                ).to_csv(predictions_file, index=False)

            # Save analysis summary
            summary_file = os.path.join(self.output_path, "analysis_summary.json")
            self.analysis_summary = {
                "analysis_date": datetime.now().isoformat(),
                "total_transactions": (
                    len(self.transactions_df) if self.transactions_df is not None else 0
                ),
                "total_recommendations": (
                    len(self.association_rules_df)
                    if self.association_rules_df is not None
                    else 0
                ),
                "total_predictions": (
                    len(self.predictions_df) if self.predictions_df is not None else 0
                ),
                "status": "completed",
            }

            with open(summary_file, "w") as f:
                json.dump(self.analysis_summary, f, indent=2)

            print(f"Saved analysis summary to {summary_file}")
            return True

        except Exception as e:
            print(f"Error saving results: {e}")
            return False

    def run_analysis(self):
        """Run the complete analysis pipeline"""
        print("Starting inventory analysis...")

        if not self.load_data():
            return False

        if not self.perform_apriori_analysis():
            return False

        if not self.perform_demand_prediction():
            return False

        if not self.save_results():
            return False

        print("Analysis completed successfully!")
        return True


def main():
    if len(sys.argv) < 3:
        print("Usage: python analyze_inventory.py <input_csv_path> <output_directory>")
        sys.exit(1)

    input_path = sys.argv[1]
    output_path = sys.argv[2]

    analyzer = InventoryAnalyzer(input_path, output_path)
    success = analyzer.run_analysis()

    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
