# predict.py - Script untuk melakukan prediksi menggunakan model yang sudah dilatih
import os
import sys
import pandas as pd
import numpy as np
import joblib
import warnings
from datetime import datetime, timedelta

# Suppress warnings for cleaner output
warnings.filterwarnings("ignore")


def log_message(message, level="INFO"):
    """Simple logging function"""
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{timestamp}] {level}: {message}")


def load_model(model_path):
    """Load trained model"""
    try:
        if not os.path.exists(model_path):
            raise FileNotFoundError(f"Model tidak ditemukan: {model_path}")

        model = joblib.load(model_path)
        log_message(f"✓ Model berhasil dimuat dari {model_path}")
        return model
    except Exception as e:
        log_message(f"Error loading model: {e}", "ERROR")
        raise


def prepare_daily_prediction_input(nama_barang, lag1, lag2, lag3):
    """
    Menyiapkan input untuk prediksi harian

    Args:
        nama_barang (str): Nama produk
        lag1 (int): Penjualan kemarin
        lag2 (int): Penjualan 2 hari lalu
        lag3 (int): Penjualan 3 hari lalu

    Returns:
        DataFrame: Input yang siap untuk prediksi
    """
    try:
        input_data = pd.DataFrame(
            {
                "nama_barang": [nama_barang],
                "lag1": [lag1],
                "lag2": [lag2],
                "lag3": [lag3],
            }
        )

        log_message("📊 INPUT PREDIKSI HARIAN:")
        log_message(f"   🏷️  Produk: {nama_barang}")
        log_message(f"   📈 Kemarin (lag1): {lag1} unit")
        log_message(f"   📈 2 hari lalu (lag2): {lag2} unit")
        log_message(f"   📈 3 hari lalu (lag3): {lag3} unit")

        return input_data

    except Exception as e:
        log_message(f"Error preparing daily input: {e}", "ERROR")
        raise


def prepare_monthly_prediction_input(nama_barang, prev_month_total):
    """
    Menyiapkan input untuk prediksi bulanan

    Args:
        nama_barang (str): Nama produk
        prev_month_total (int): Total penjualan bulan sebelumnya

    Returns:
        DataFrame: Input yang siap untuk prediksi
    """
    try:
        input_data = pd.DataFrame(
            {"nama_barang": [nama_barang], "prev_month_total": [prev_month_total]}
        )

        log_message("📊 INPUT PREDIKSI BULANAN:")
        log_message(f"   🏷️  Produk: {nama_barang}")
        log_message(f"   📈 Total bulan lalu: {prev_month_total} unit")

        return input_data

    except Exception as e:
        log_message(f"Error preparing monthly input: {e}", "ERROR")
        raise


def make_daily_prediction(
    nama_barang, lag1, lag2, lag3, model_path="model/rf_stock_predictor_daily.pkl"
):
    """
    Melakukan prediksi penjualan harian

    Args:
        nama_barang (str): Nama produk
        lag1 (int): Penjualan kemarin
        lag2 (int): Penjualan 2 hari lalu
        lag3 (int): Penjualan 3 hari lalu
        model_path (str): Path ke model file

    Returns:
        float: Prediksi total penjualan hari ini
    """
    try:
        log_message("🗓️  MEMULAI PREDIKSI HARIAN")
        log_message("=" * 50)

        # Load model
        model = load_model(model_path)

        # Prepare input
        input_data = prepare_daily_prediction_input(nama_barang, lag1, lag2, lag3)

        # Make prediction
        prediction = model.predict(input_data)[0]
        prediction = max(
            0, round(prediction)
        )  # Ensure non-negative and round to integer

        log_message("🎯 HASIL PREDIKSI HARIAN:")
        log_message(f"   📦 Produk: {nama_barang}")
        log_message(f"   🔮 Prediksi hari ini: {prediction} unit")
        log_message("=" * 50)

        return prediction

    except Exception as e:
        log_message(f"Error dalam prediksi harian: {e}", "ERROR")
        raise


def make_monthly_prediction(
    nama_barang, prev_month_total, model_path="model/rf_stock_predictor_monthly.pkl"
):
    """
    Melakukan prediksi penjualan bulanan

    Args:
        nama_barang (str): Nama produk
        prev_month_total (int): Total penjualan bulan sebelumnya
        model_path (str): Path ke model file

    Returns:
        float: Prediksi total penjualan bulan ini
    """
    try:
        log_message("📅 MEMULAI PREDIKSI BULANAN")
        log_message("=" * 50)

        # Load model
        model = load_model(model_path)

        # Prepare input
        input_data = prepare_monthly_prediction_input(nama_barang, prev_month_total)

        # Make prediction
        prediction = model.predict(input_data)[0]
        prediction = max(
            0, round(prediction)
        )  # Ensure non-negative and round to integer

        log_message("🎯 HASIL PREDIKSI BULANAN:")
        log_message(f"   📦 Produk: {nama_barang}")
        log_message(f"   🔮 Prediksi bulan ini: {prediction} unit")
        log_message("=" * 50)

        return prediction

    except Exception as e:
        log_message(f"Error dalam prediksi bulanan: {e}", "ERROR")
        raise


def batch_daily_predictions(
    products_data, model_path="model/rf_stock_predictor_daily.pkl"
):
    """
    Melakukan prediksi harian untuk multiple produk sekaligus

    Args:
        products_data (list): List of dict dengan keys: nama_barang, lag1, lag2, lag3
        model_path (str): Path ke model file

    Returns:
        DataFrame: Hasil prediksi untuk semua produk
    """
    try:
        log_message("🗓️  MEMULAI BATCH PREDIKSI HARIAN")
        log_message("=" * 50)

        # Load model
        model = load_model(model_path)

        # Prepare batch input
        input_df = pd.DataFrame(products_data)
        required_cols = ["nama_barang", "lag1", "lag2", "lag3"]

        if not all(col in input_df.columns for col in required_cols):
            raise ValueError(f"Input harus memiliki kolom: {required_cols}")

        log_message(f"📊 Processing {len(input_df)} produk...")

        # Make predictions
        predictions = model.predict(input_df[required_cols])
        predictions = np.maximum(
            0, np.round(predictions)
        )  # Ensure non-negative and round

        # Prepare results
        results_df = input_df.copy()
        results_df["prediksi_hari_ini"] = predictions

        log_message("🎯 HASIL BATCH PREDIKSI HARIAN:")
        for idx, row in results_df.iterrows():
            log_message(
                f"   📦 {row['nama_barang']}: {int(row['prediksi_hari_ini'])} unit"
            )

        log_message("=" * 50)

        return results_df

    except Exception as e:
        log_message(f"Error dalam batch prediksi harian: {e}", "ERROR")
        raise


def batch_monthly_predictions(
    products_data, model_path="model/rf_stock_predictor_monthly.pkl"
):
    """
    Melakukan prediksi bulanan untuk multiple produk sekaligus

    Args:
        products_data (list): List of dict dengan keys: nama_barang, prev_month_total
        model_path (str): Path ke model file

    Returns:
        DataFrame: Hasil prediksi untuk semua produk
    """
    try:
        log_message("📅 MEMULAI BATCH PREDIKSI BULANAN")
        log_message("=" * 50)

        # Load model
        model = load_model(model_path)

        # Prepare batch input
        input_df = pd.DataFrame(products_data)
        required_cols = ["nama_barang", "prev_month_total"]

        if not all(col in input_df.columns for col in required_cols):
            raise ValueError(f"Input harus memiliki kolom: {required_cols}")

        log_message(f"📊 Processing {len(input_df)} produk...")

        # Make predictions
        predictions = model.predict(input_df[required_cols])
        predictions = np.maximum(
            0, np.round(predictions)
        )  # Ensure non-negative and round

        # Prepare results
        results_df = input_df.copy()
        results_df["prediksi_bulan_ini"] = predictions

        log_message("🎯 HASIL BATCH PREDIKSI BULANAN:")
        for idx, row in results_df.iterrows():
            log_message(
                f"   📦 {row['nama_barang']}: {int(row['prediksi_bulan_ini'])} unit"
            )

        log_message("=" * 50)

        return results_df

    except Exception as e:
        log_message(f"Error dalam batch prediksi bulanan: {e}", "ERROR")
        raise


if __name__ == "__main__":
    if len(sys.argv) < 3:
        log_message(
            "Usage: python predict.py <nama_barang> <type> [parameters...]", "ERROR"
        )
        log_message(
            "  Daily: python predict.py 'Nama Produk' 'hari' <lag1> <lag2> <lag3>",
            "INFO",
        )
        log_message(
            "  Monthly: python predict.py 'Nama Produk' 'bulan' <prev_month_total>",
            "INFO",
        )
        sys.exit(1)

    nama_barang = sys.argv[1]
    type_of_prediction = sys.argv[2]

    try:
        if type_of_prediction == "hari":
            if len(sys.argv) != 6:
                log_message(
                    "Error: Prediksi harian memerlukan 3 parameter lag", "ERROR"
                )
                sys.exit(1)
            lag1 = int(sys.argv[3])
            lag2 = int(sys.argv[4])
            lag3 = int(sys.argv[5])
            result = make_daily_prediction(nama_barang, lag1, lag2, lag3)

            # Output for API/system integration
            print(f"PREDICTION_RESULT:{result}")

        elif type_of_prediction == "bulan":
            if len(sys.argv) != 4:
                log_message("Error: Prediksi bulanan memerlukan 1 parameter", "ERROR")
                sys.exit(1)
            prev_month_total = int(sys.argv[3])
            result = make_monthly_prediction(nama_barang, prev_month_total)

            # Output for API/system integration
            print(f"PREDICTION_RESULT:{result}")

        else:
            log_message(
                "Tipe prediksi tidak dikenali. Gunakan 'hari' atau 'bulan'.", "ERROR"
            )
            sys.exit(1)

    except ValueError as e:
        log_message(f"Error: Parameter harus berupa angka - {e}", "ERROR")
        sys.exit(1)
    except Exception as e:
        log_message(f"Error dalam prediksi: {e}", "ERROR")
        sys.exit(1)

    sys.exit(0)
