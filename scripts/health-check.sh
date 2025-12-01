#!/bin/bash

# ============================================
# CMMS Health Check Script
# ============================================
# Monitor application health and send alerts
# Run this via cron every 5-15 minutes
# ============================================

set -e

# ============================================
# CONFIGURATION
# ============================================

APP_DIR="/var/www/cmmseng"
APP_URL="https://your-domain.com"  # UPDATE THIS!
LOG_FILE="$APP_DIR/storage/logs/health-check.log"
MAX_LOG_LINES=1000

# Thresholds
DISK_THRESHOLD=90          # Alert if disk usage > 90%
MEMORY_THRESHOLD=90        # Alert if memory usage > 90%
CPU_THRESHOLD=90           # Alert if CPU usage > 90%
RESPONSE_TIME_THRESHOLD=5  # Alert if response time > 5 seconds

# Notification settings
ENABLE_TELEGRAM=false
TELEGRAM_BOT_TOKEN=""  # UPDATE THIS if using Telegram
TELEGRAM_CHAT_ID=""    # UPDATE THIS if using Telegram

# ============================================
# FUNCTIONS
# ============================================

# Log message
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Send Telegram alert
send_alert() {
    SEVERITY="$1"
    MESSAGE="$2"
    
    case "$SEVERITY" in
        "CRITICAL")
            EMOJI="🔴"
            ;;
        "WARNING")
            EMOJI="⚠️"
            ;;
        "INFO")
            EMOJI="ℹ️"
            ;;
        *)
            EMOJI="📊"
            ;;
    esac
    
    if [ "$ENABLE_TELEGRAM" = true ] && [ -n "$TELEGRAM_BOT_TOKEN" ] && [ -n "$TELEGRAM_CHAT_ID" ]; then
        FULL_MESSAGE="$EMOJI CMMS Health Check
$SEVERITY: $MESSAGE
Time: $(date '+%Y-%m-%d %H:%M:%S')"
        
        curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
            -d chat_id="${TELEGRAM_CHAT_ID}" \
            -d text="${FULL_MESSAGE}" > /dev/null
    fi
}

# Check HTTP response
check_http() {
    log "Checking HTTP response..."
    
    START_TIME=$(date +%s.%N)
    HTTP_CODE=$(curl -o /dev/null -s -w "%{http_code}" -m 10 "$APP_URL/health" || echo "000")
    END_TIME=$(date +%s.%N)
    
    RESPONSE_TIME=$(echo "$END_TIME - $START_TIME" | bc)
    
    if [ "$HTTP_CODE" != "200" ]; then
        log "❌ HTTP check failed (Code: $HTTP_CODE)"
        send_alert "CRITICAL" "Application unreachable! HTTP Code: $HTTP_CODE"
        return 1
    fi
    
    # Check response time
    RESPONSE_TIME_INT=${RESPONSE_TIME%.*}
    if [ "$RESPONSE_TIME_INT" -gt "$RESPONSE_TIME_THRESHOLD" ]; then
        log "⚠️ Slow response time: ${RESPONSE_TIME}s"
        send_alert "WARNING" "Slow response time: ${RESPONSE_TIME}s (threshold: ${RESPONSE_TIME_THRESHOLD}s)"
    fi
    
    log "✓ HTTP check passed (${RESPONSE_TIME}s)"
    return 0
}

# Check database connection
check_database() {
    log "Checking database connection..."
    
    cd "$APP_DIR"
    
    if php artisan db:monitor &> /dev/null; then
        log "✓ Database check passed"
        return 0
    else
        log "❌ Database check failed"
        send_alert "CRITICAL" "Database connection failed!"
        return 1
    fi
}

# Check queue workers
check_queue() {
    log "Checking queue workers..."
    
    if ! systemctl is-active --quiet supervisor; then
        log "❌ Supervisor not running"
        send_alert "CRITICAL" "Supervisor service not running!"
        return 1
    fi
    
    # Count running workers
    RUNNING_WORKERS=$(supervisorctl status cmmseng-worker:* 2>/dev/null | grep -c "RUNNING" || echo "0")
    
    if [ "$RUNNING_WORKERS" -eq 0 ]; then
        log "❌ No queue workers running"
        send_alert "CRITICAL" "No queue workers running!"
        
        # Try to start workers
        log "Attempting to start workers..."
        supervisorctl start cmmseng-worker:* &> /dev/null
        
        return 1
    fi
    
    log "✓ Queue workers running ($RUNNING_WORKERS)"
    return 0
}

# Check disk space
check_disk() {
    log "Checking disk space..."
    
    DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$DISK_USAGE" -gt "$DISK_THRESHOLD" ]; then
        log "⚠️ Disk usage high: ${DISK_USAGE}%"
        send_alert "WARNING" "Disk usage at ${DISK_USAGE}% (threshold: ${DISK_THRESHOLD}%)"
        return 1
    fi
    
    log "✓ Disk usage OK (${DISK_USAGE}%)"
    return 0
}

# Check memory
check_memory() {
    log "Checking memory usage..."
    
    MEMORY_USAGE=$(free | grep Mem | awk '{print int(($3/$2) * 100)}')
    
    if [ "$MEMORY_USAGE" -gt "$MEMORY_THRESHOLD" ]; then
        log "⚠️ Memory usage high: ${MEMORY_USAGE}%"
        send_alert "WARNING" "Memory usage at ${MEMORY_USAGE}% (threshold: ${MEMORY_THRESHOLD}%)"
        return 1
    fi
    
    log "✓ Memory usage OK (${MEMORY_USAGE}%)"
    return 0
}

# Check CPU
check_cpu() {
    log "Checking CPU usage..."
    
    CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print int(100 - $1)}')
    
    if [ "$CPU_USAGE" -gt "$CPU_THRESHOLD" ]; then
        log "⚠️ CPU usage high: ${CPU_USAGE}%"
        send_alert "WARNING" "CPU usage at ${CPU_USAGE}% (threshold: ${CPU_THRESHOLD}%)"
        return 1
    fi
    
    log "✓ CPU usage OK (${CPU_USAGE}%)"
    return 0
}

# Check Laravel logs for errors
check_logs() {
    log "Checking Laravel logs for errors..."
    
    LARAVEL_LOG="$APP_DIR/storage/logs/laravel.log"
    
    if [ ! -f "$LARAVEL_LOG" ]; then
        log "⚠️ Laravel log file not found"
        return 0
    fi
    
    # Count critical errors in last 100 lines
    ERROR_COUNT=$(tail -100 "$LARAVEL_LOG" | grep -c "ERROR\|CRITICAL\|EMERGENCY" || echo "0")
    
    if [ "$ERROR_COUNT" -gt 10 ]; then
        log "⚠️ High error count: $ERROR_COUNT errors in last 100 lines"
        send_alert "WARNING" "High error count: $ERROR_COUNT errors detected in logs"
        return 1
    fi
    
    log "✓ Log check passed ($ERROR_COUNT errors)"
    return 0
}

# Rotate log file if too large
rotate_log() {
    if [ -f "$LOG_FILE" ]; then
        LINE_COUNT=$(wc -l < "$LOG_FILE")
        
        if [ "$LINE_COUNT" -gt "$MAX_LOG_LINES" ]; then
            tail -"$MAX_LOG_LINES" "$LOG_FILE" > "$LOG_FILE.tmp"
            mv "$LOG_FILE.tmp" "$LOG_FILE"
        fi
    fi
}

# ============================================
# MAIN SCRIPT
# ============================================

# Create log directory if not exists
mkdir -p "$(dirname "$LOG_FILE")"

log "============================================"
log "Starting health check..."

FAILED_CHECKS=0

# Run all checks
check_http || ((FAILED_CHECKS++))
check_database || ((FAILED_CHECKS++))
check_queue || ((FAILED_CHECKS++))
check_disk || ((FAILED_CHECKS++))
check_memory || ((FAILED_CHECKS++))
check_cpu || ((FAILED_CHECKS++))
check_logs || ((FAILED_CHECKS++))

# Summary
log "============================================"
if [ "$FAILED_CHECKS" -eq 0 ]; then
    log "✓ All checks passed"
else
    log "⚠️ $FAILED_CHECKS check(s) failed"
fi
log "Health check complete"
log ""

# Rotate log file
rotate_log

exit $FAILED_CHECKS
