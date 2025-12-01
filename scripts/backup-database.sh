#!/bin/bash

# ============================================
# CMMS Database Backup Script
# ============================================
# Automated MySQL database backup with
# compression and retention management
# ============================================

set -e  # Exit on error

# ============================================
# CONFIGURATION
# ============================================

# Database credentials
DB_NAME="cmmseng"
DB_USER="cmmseng_user"
DB_PASS="your-secure-password"  # UPDATE THIS!
DB_HOST="127.0.0.1"
DB_PORT="3306"

# Backup settings
BACKUP_DIR="/var/backups/cmmseng/database"
RETENTION_DAYS=30
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="cmmseng_${DATE}.sql"
COMPRESSED_FILE="${BACKUP_FILE}.gz"

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
echo "CMMS Database Backup"
echo "============================================"
echo "Started: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

# Check if mysqldump is available
if ! command -v mysqldump &> /dev/null; then
    echo "ERROR: mysqldump not found!"
    send_telegram "❌ CMMS Backup Failed: mysqldump not found"
    exit 1
fi

# Test database connection
echo "Testing database connection..."
if ! mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME" &> /dev/null; then
    echo "ERROR: Cannot connect to database!"
    send_telegram "❌ CMMS Backup Failed: Cannot connect to database"
    exit 1
fi
echo "✓ Database connection successful"
echo ""

# Perform backup
echo "Creating database backup..."
START_TIME=$(date +%s)

mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    --events \
    "$DB_NAME" > "$BACKUP_DIR/$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✓ Database dump created"
else
    echo "ERROR: Database dump failed!"
    send_telegram "❌ CMMS Backup Failed: Database dump error"
    exit 1
fi

# Compress backup
echo "Compressing backup..."
gzip -f "$BACKUP_DIR/$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✓ Backup compressed"
else
    echo "ERROR: Compression failed!"
    send_telegram "❌ CMMS Backup Failed: Compression error"
    exit 1
fi

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

# Get backup file size
FILE_SIZE=$(stat -f%z "$BACKUP_DIR/$COMPRESSED_FILE" 2>/dev/null || stat -c%s "$BACKUP_DIR/$COMPRESSED_FILE" 2>/dev/null)
FORMATTED_SIZE=$(format_size "$FILE_SIZE")

echo ""
echo "Backup Details:"
echo "  File: $COMPRESSED_FILE"
echo "  Size: $FORMATTED_SIZE"
echo "  Duration: ${DURATION}s"
echo "  Location: $BACKUP_DIR"
echo ""

# Clean up old backups
echo "Removing backups older than $RETENTION_DAYS days..."
DELETED_COUNT=$(find "$BACKUP_DIR" -name "cmmseng_*.sql.gz" -mtime +$RETENTION_DAYS -type f -delete -print | wc -l)
echo "✓ Removed $DELETED_COUNT old backup(s)"
echo ""

# Show remaining backups
TOTAL_BACKUPS=$(find "$BACKUP_DIR" -name "cmmseng_*.sql.gz" -type f | wc -l)
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
echo "Total backups: $TOTAL_BACKUPS ($TOTAL_SIZE)"
echo ""

echo "============================================"
echo "Backup Complete!"
echo "============================================"
echo "Finished: $(date '+%Y-%m-%d %H:%M:%S')"

# Send success notification
send_telegram "✅ CMMS Database Backup Complete
File: ${COMPRESSED_FILE}
Size: ${FORMATTED_SIZE}
Duration: ${DURATION}s
Total Backups: ${TOTAL_BACKUPS}"

exit 0
