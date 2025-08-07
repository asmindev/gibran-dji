# Windows Compatibility Guide

This document### 4. Cross-Platform Queue Management

The queue worker command (`app/Console/Commands/QueueWorkerCommand.php`) now supports:

- **Windows process management**: Uses `tasklist` and `taskkill` instead of `ps` and `pkill`
- **Background process handling**: Uses `start /B` on Windows, traditional `&` on Unix
- **Process detection**: Cross-platform process discovery and management
- **Enhanced status reporting**: Shows platform-specific information and instructions

### 5. Cross-Platform Scripts

#### Queue Management Scripts
- `queue_manager.bat` - Windows batch script for queue management
- `queue_manager.sh` - Unix shell script for queue management

#### System Check Scripts

#### Windows Batch Script

- `check_scheduler.bat` - Windows equivalent of the shell script
- Uses Windows-specific commands like `tasklist` instead of `ps`
- Uses PowerShell for advanced operations like reading log files

#### Original Shell Script

- `check_scheduler.sh` - Remains for Unix-like systems
- Uses standard Unix commandse Windows compatibility improvements made to the Gibran stock prediction system.

## Overview

The system has been updated to work seamlessly on both Windows and Unix-like systems (Linux, macOS). The main compatibility issues that were addressed include:

1. **File path separators** (`/` vs `\`)
2. **Shell command syntax differences**
3. **Python execution differences**
4. **Process management differences**
5. **Virtual environment activation**

## Key Components

### 1. PlatformCompatibilityService

A new service class (`app/Services/PlatformCompatibilityService.php`) that provides:

-   **Cross-platform path building**: Uses `DIRECTORY_SEPARATOR` constant
-   **Python command detection**: Automatically detects `python` vs `python3`
-   **Virtual environment support**: Handles both Windows and Unix venv activation
-   **Process management**: Cross-platform process checking and killing
-   **Command execution**: Unified command execution with proper working directory handling

### 2. Updated TrainStockPredictionModel Job

The job (`app/Jobs/TrainStockPredictionModel.php`) now:

-   Uses the `PlatformCompatibilityService` for all path operations
-   Properly handles virtual environment activation on both platforms
-   Provides detailed logging of platform-specific information
-   Uses correct directory separators for file operations

### 3. Enhanced TrainModelCommand

The command (`app/Console/Commands/TrainModelCommand.php`) now includes:

-   `--info` flag to show system compatibility information
-   Python environment verification
-   Platform-specific monitoring suggestions
-   Better error reporting with context

### 4. Cross-Platform Scripts

#### Windows Batch Script

-   `check_scheduler.bat` - Windows equivalent of the shell script
-   Uses Windows-specific commands like `tasklist` instead of `ps`
-   Uses PowerShell for advanced operations like reading log files

#### Original Shell Script

-   `check_scheduler.sh` - Remains for Unix-like systems
-   Uses standard Unix commands

## Usage Examples

### Training the Model

```bash
# Basic training
php artisan model:train

# Force training (bypass in-progress check)
php artisan model:train --force

# Show system compatibility information
php artisan model:train --info
```

### Managing Queue Workers

```bash
# Start queue worker
php artisan queue:manage start

# Stop queue worker  
php artisan queue:manage stop

# Check worker status
php artisan queue:manage status

# Restart worker
php artisan queue:manage restart
```

### Monitoring Progress

#### On Windows:

```powershell
# Monitor logs
Get-Content storage\logs\laravel.log -Tail 20 -Wait

# Check scheduler
.\check_scheduler.bat
```

#### On Unix-like systems:

```bash
# Monitor logs
tail -f storage/logs/laravel.log

# Check scheduler
./check_scheduler.sh
```

## Python Virtual Environment Support

The system automatically detects and uses Python virtual environments:

### Windows

-   Looks for: `scripts\.venv\Scripts\python.exe`
-   Activation: `scripts\.venv\Scripts\activate.bat`

### Unix-like

-   Looks for: `scripts/.venv/bin/python`
-   Activation: `scripts/.venv/bin/activate`

### Fallback

If no virtual environment is found, the system uses:

-   Windows: `python`
-   Unix-like: `python3`

## File Path Handling

All file paths now use:

-   `DIRECTORY_SEPARATOR` constant for cross-platform compatibility
-   `PlatformCompatibilityService::buildPath()` for consistent path building
-   `PlatformCompatibilityService::normalizePath()` for path normalization

## Error Handling and Logging

Enhanced error handling includes:

-   Platform detection and logging
-   Virtual environment detection status
-   Command execution results with full context
-   System information in error reports

## Testing Compatibility

### Check System Information

```bash
php artisan model:train --info
```

This command displays:

-   Operating system family
-   Python command availability
-   Virtual environment status
-   Scripts directory validation
-   File existence checks

### Verify Python Environment

The system automatically tests Python execution before training:

-   Checks if Python script exists
-   Validates Python command works
-   Reports virtual environment usage

## Troubleshooting

### Common Windows Issues

1. **Python not found**

    - Ensure Python is installed and in PATH
    - Or use virtual environment in `scripts\.venv\`

2. **Permission denied**

    - Run command prompt as Administrator
    - Check file permissions in scripts directory

3. **Path too long**
    - Enable long path support in Windows
    - Or use shorter base directory path

### Common Unix Issues

1. **Python3 not found**

    - Install Python 3: `sudo apt install python3` (Ubuntu)
    - Or use virtual environment

2. **Permission denied**
    - Check file permissions: `chmod +x check_scheduler.sh`
    - Ensure scripts directory is writable

## Migration from Previous Version

If upgrading from a previous version:

1. **Backup existing models**: Copy `scripts/model/` directory
2. **Update dependencies**: Run `composer install`
3. **Test compatibility**: Run `php artisan model:train --info`
4. **Update cron jobs**: Use appropriate script for your platform

## Directory Structure

```text
project/
├── app/
│   ├── Console/Commands/
│   │   ├── TrainModelCommand.php          # Enhanced with --info flag
│   │   └── QueueWorkerCommand.php         # Cross-platform queue management
│   ├── Jobs/
│   │   └── TrainStockPredictionModel.php  # Cross-platform compatible
│   └── Services/
│       └── PlatformCompatibilityService.php # New service class
├── scripts/
│   ├── .venv/                             # Virtual environment (optional)
│   ├── stock_predictor.py                 # Python script (unchanged)
│   └── data/                              # CSV data directory
├── check_scheduler.sh                     # Unix shell script
├── check_scheduler.bat                    # Windows batch script
├── queue_manager.sh                       # Unix queue management script
└── queue_manager.bat                      # Windows queue management script
```

## Security Considerations

-   All user inputs are properly escaped in shell commands
-   File paths are validated before use
-   Virtual environment paths are checked for existence
-   Command execution includes proper error handling

## Performance Notes

-   Path operations use native PHP constants for best performance
-   Command execution includes proper working directory handling
-   Virtual environment detection is cached during execution
-   Platform detection happens once per request

This compatibility layer ensures the stock prediction system works reliably across different operating systems while maintaining the same functionality and performance characteristics.
