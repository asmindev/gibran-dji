#!/usr/bin/env python3
"""
Simple logging utility for stock prediction system
Setup file-based logging without console output
"""

import logging
import os
from pathlib import Path

# Create logs directory if it doesn't exist

current_dir = Path(__file__).parent
log_dir = current_dir / "logs"
log_dir.mkdir(exist_ok=True)

file_path = log_dir / "stock.log"

# Create logger
logger = logging.getLogger("stock_prediction")

# Set logging level
logger.setLevel(logging.INFO)

# Clear any existing handlers to avoid duplicates
logger.handlers.clear()

# Create file handler (no console handler)
file_handler = logging.FileHandler(file_path, encoding="utf-8")
file_handler.setLevel(logging.INFO)

# Create formatter
formatter = logging.Formatter(
    "%(asctime)s - %(levelname)s - %(message)s", datefmt="%Y-%m-%d %H:%M:%S"
)
file_handler.setFormatter(formatter)

# Add handler to logger
logger.addHandler(file_handler)

# Prevent propagation to root logger (no console output)
logger.propagate = False
