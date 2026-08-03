#!/bin/bash
# start-workers.sh — Run Laravel queue workers manually (without Supervisor)
# Usage: bash start-workers.sh
# For production use Supervisor instead (see supervisor.conf)

ARTISAN="/usr/bin/php /var/www/html/whatsapp/artisan"
LOG="/var/www/html/whatsapp/storage/logs/worker.log"

echo "[$(date)] Starting 4 queue workers (broadcasts + default)…" | tee -a "$LOG"

for i in 1 2 3 4; do
    $ARTISAN queue:work database \
        --queue=broadcasts,default \
        --tries=5 \
        --timeout=300 \
        --sleep=1 \
        --max-jobs=500 \
        >> "$LOG" 2>&1 &
    echo "[$(date)] Worker $i started (PID $!)" | tee -a "$LOG"
done

echo ""
echo "Workers running. To stop all: pkill -f 'queue:work'"
echo "Logs: tail -f $LOG"
