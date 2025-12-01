#!/bin/bash

# ============================================
# CMMS File Backup Script
# ============================================
# Backup uploaded files and storage directory
# with compression and retention management
# ============================================

set -e  # Exit on error

# ============================================
# CONFIGURATION
# ============================================

# Application settings
APP_DIR="/var/www/cmmseng"
STORAGE_DIR="$APP_DIR/storage"

# Backup settings
BACKUP_DIR="/var/backups/cmmseng/files"
RETENTION_DAYS=7
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="storage_${DATE}.tar.gz"

# Notification settings (optional)
ENABLE_TELEGRAM=false
TELEGRAM_BOT_TOKEN=""  # UPDATE THIS if using Telegram
TELEGRAM_CHAT_ID=""    # UPDATE THIS if using Telegram

# ============================================
# FUNCTIONS
# ============================================

# Send Telegram notification
send_telegram() {
    if [ "$ENABLE_TELEGRAM" = true ] && [ -n "$TELEGRAM_BOT_TOKEN" ] && [ -n "$TELEGRAM_CHAT_ID" ]; then
        MESSAGE="$1"
        curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
            -d chat_id="${TELEGRAM_CHAT_ID}" \
            -d text="${MESSAGE}" > /dev/null
    fi
}

# Format bytes to human readable
format_size() {
    numfmt --to=iec-i --suffix=B "$1"
}

# ============================================
# MAIN SCRIPT
# ============================================

echo "============================================"
echo "CMMS File Backup"
echo "============================================"
echo "Started: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

# Check if storage directory exists
if [ ! -d "$STORAGE_DIR" ]; then
    echo "ERROR: Storage directory not found: $STORAGE_DIR"
    send_telegram "❌ CMMS File Backup Failed: Storage directory not found"
    exit 1
fi

# Get storage directory size before backup
STORAGE_SIZE=$(du -sh "$STORAGE_DIR" | cut -f1)
echo "Storage directory size: $STORAGE_SIZE"
echo ""

# Perform backup
echo "Creating file backup..."
START_TIME=$(date +%s)

# Create tar archive with gzip compression
# Exclude cache and log files to reduce size
tar -czf "$BACKUP_DIR/$BACKUP_FILE" \
    -C "$APP_DIR" \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='storage/logs/*' \
    storage

if [ $? -eq 0 ]; then
    echo "✓ File backup created"
else
    echo "ERROR: File backup failed!"
    send_telegram "❌ CMMS File Backup Failed: Archive creation error"
    exit 1
fi

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

# Get backup file size
FILE_SIZE=$(stat -f%z "$BACKUP_DIR/$BACKUP_FILE" 2>/dev/null || stat -c%s "$BACKUP_DIR/$BACKUP_FILE" 2>/dev/null)
FORMATTED_SIZE=$(format_size "$FILE_SIZE")

echo ""
echo "Backup Details:"
echo "  File: $BACKUP_FILE"
echo "  Size: $FORMATTED_SIZE"
echo "  Duration: ${DURATION}s"
echo "  Location: $BACKUP_DIR"
echo ""

# Clean up old backups
echo "Removing backups older than $RETENTION_DAYS days..."
DELETED_COUNT=$(find "$BACKUP_DIR" -name "storage_*.tar.gz" -mtime +$RETENTION_DAYS -type f -delete -print | wc -l)
echo "✓ Removed $DELETED_COUNT old backup(s)"
echo ""

# Show remaining backups
TOTAL_BACKUPS=$(find "$BACKUP_DIR" -name "storage_*.tar.gz" -type f | wc -l)
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
echo "Total backups: $TOTAL_BACKUPS ($TOTAL_SIZE)"
echo ""

echo "============================================"
echo "Backup Complete!"
echo "============================================"
echo "Finished: $(date '+%Y-%m-%d %H:%M:%S')"

# Send success notification
send_telegram "✅ CMMS File Backup Complete
File: ${BACKUP_FILE}
Size: ${FORMATTED_SIZE}
Duration: ${DURATION}s
Total Backups: ${TOTAL_BACKUPS}"

exit 0
