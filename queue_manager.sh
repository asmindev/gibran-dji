#!/bin/bash

echo "=== QUEUE WORKER MANAGEMENT ==="
echo "Platform: Unix/Linux"
echo ""

if [ $# -eq 0 ]; then
    echo "Usage: ./queue_manager.sh [start|stop|status|restart]"
    echo ""
    echo "Commands:"
    echo "  start   - Start the queue worker"
    echo "  stop    - Stop the queue worker"
    echo "  status  - Check worker status"
    echo "  restart - Restart the worker"
    echo ""
    exit 1
fi

ACTION=$1
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Action: $ACTION"
echo "Working directory: $(pwd)"
echo ""

case $ACTION in
    start)
        echo "Starting queue worker..."
        php artisan queue:manage start
        ;;
    stop)
        echo "Stopping queue worker..."
        php artisan queue:manage stop
        ;;
    status)
        echo "Checking queue worker status..."
        php artisan queue:manage status
        ;;
    restart)
        echo "Restarting queue worker..."
        php artisan queue:manage restart
        ;;
    *)
        echo "Invalid action: $ACTION"
        echo "Use: start, stop, status, or restart"
        exit 1
        ;;
esac

echo ""
echo "Operation completed at $(date)"
