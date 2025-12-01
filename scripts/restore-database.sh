#!/bin/bash

# ============================================
# CMMS Database Restore Script
# ============================================
# Restore database from compressed backup
# with safety checks and confirmation
# ============================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ============================================
# CONFIGURATION
# ============================================

# Database credentials
DB_NAME="cmmseng"
DB_USER="cmmseng_user"
DB_PASS="your-secure-password"  # UPDATE THIS!
DB_HOST="127.0.0.1"
DB_PORT="3306"

# Backup directory
BACKUP_DIR="/var/backups/cmmseng/database"

# ============================================
# FUNCTIONS
# ============================================

# Show available backups
list_backups() {
    echo -e "${YELLOW}Available backups:${NC}"
    echo ""
    
    BACKUPS=($(find "$BACKUP_DIR" -name "cmmseng_*.sql.gz" -type f | sort -r))
    
    if [ ${#BACKUPS[@]} -eq 0 ]; then
        echo -e "${RED}No backups found in $BACKUP_DIR${NC}"
        exit 1
    fi
    
    for i in "${!BACKUPS[@]}"; do
        FILE="${BACKUPS[$i]}"
        FILENAME=$(basename "$FILE")
        SIZE=$(stat -f%z "$FILE" 2>/dev/null || stat -c%s "$FILE" 2>/dev/null)
        FORMATTED_SIZE=$(numfmt --to=iec-i --suffix=B "$SIZE")
        DATE=$(stat -f "%Sm" -t "%Y-%m-%d %H:%M:%S" "$FILE" 2>/dev/null || stat -c "%y" "$FILE" 2>/dev/null | cut -d'.' -f1)
        
        echo "  [$((i+1))] $FILENAME"
        echo "      Size: $FORMATTED_SIZE"
        echo "      Date: $DATE"
        echo ""
    done
}

# Confirm action
confirm() {
    read -p "$1 [y/N]: " response
    case "$response" in
        [yY][eE][sS]|[yY]) 
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

# ============================================
# MAIN SCRIPT
# ============================================

echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}CMMS Database Restore${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""

# Check if backup directory exists
if [ ! -d "$BACKUP_DIR" ]; then
    echo -e "${RED}ERROR: Backup directory not found: $BACKUP_DIR${NC}"
    exit 1
fi

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root (sudo)${NC}"
    exit 1
fi

# List available backups
list_backups

# Get user selection
BACKUPS=($(find "$BACKUP_DIR" -name "cmmseng_*.sql.gz" -type f | sort -r))
read -p "Select backup number to restore [1-${#BACKUPS[@]}]: " SELECTION

# Validate selection
if ! [[ "$SELECTION" =~ ^[0-9]+$ ]] || [ "$SELECTION" -lt 1 ] || [ "$SELECTION" -gt ${#BACKUPS[@]} ]; then
    echo -e "${RED}Invalid selection!${NC}"
    exit 1
fi

BACKUP_FILE="${BACKUPS[$((SELECTION-1))]}"
BACKUP_FILENAME=$(basename "$BACKUP_FILE")

echo ""
echo -e "${YELLOW}Selected backup: $BACKUP_FILENAME${NC}"
echo ""

# Warning message
echo -e "${RED}WARNING: This will OVERWRITE the current database!${NC}"
echo -e "${RED}All existing data will be LOST!${NC}"
echo ""

# Confirm action
if ! confirm "Are you sure you want to restore this backup?"; then
    echo "Restore cancelled."
    exit 0
fi

echo ""
echo -e "${YELLOW}Creating safety backup of current database...${NC}"

# Create safety backup of current database
SAFETY_BACKUP="$BACKUP_DIR/pre_restore_$(date +"%Y%m%d_%H%M%S").sql.gz"
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    "$DB_NAME" | gzip > "$SAFETY_BACKUP"

echo -e "${GREEN}✓ Safety backup created: $(basename $SAFETY_BACKUP)${NC}"
echo ""

# Test database connection
echo -e "${YELLOW}Testing database connection...${NC}"
if ! mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME" &> /dev/null; then
    echo -e "${RED}ERROR: Cannot connect to database!${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Database connection successful${NC}"
echo ""

# Decompress backup
echo -e "${YELLOW}Decompressing backup...${NC}"
TEMP_SQL="/tmp/restore_$(date +%s).sql"
gunzip -c "$BACKUP_FILE" > "$TEMP_SQL"
echo -e "${GREEN}✓ Backup decompressed${NC}"
echo ""

# Restore database
echo -e "${YELLOW}Restoring database...${NC}"
START_TIME=$(date +%s)

mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$TEMP_SQL"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database restored successfully${NC}"
else
    echo -e "${RED}ERROR: Database restore failed!${NC}"
    echo -e "${YELLOW}Attempting to restore safety backup...${NC}"
    
    gunzip -c "$SAFETY_BACKUP" | mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Safety backup restored${NC}"
    else
        echo -e "${RED}CRITICAL: Safety backup restore failed!${NC}"
        echo -e "${RED}Manual intervention required!${NC}"
    fi
    
    rm -f "$TEMP_SQL"
    exit 1
fi

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

# Clean up
rm -f "$TEMP_SQL"

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}Restore Complete!${NC}"
echo -e "${GREEN}============================================${NC}"
echo "  Restored from: $BACKUP_FILENAME"
echo "  Duration: ${DURATION}s"
echo "  Safety backup: $(basename $SAFETY_BACKUP)"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Clear Laravel caches: php artisan optimize:clear"
echo "2. Test application functionality"
echo "3. Verify data integrity"
echo ""

exit 0
