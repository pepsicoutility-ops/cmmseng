#!/bin/bash

# ONNX Service Deployment Script for VPS
# Usage: ./deploy.sh

set -e

echo "======================================"
echo "ONNX Service Deployment Script"
echo "======================================"

# Configuration
SERVICE_NAME="onnx-service"
SERVICE_DIR="/var/www/$SERVICE_NAME"
SERVICE_USER="www-data"
LOG_DIR="/var/log/$SERVICE_NAME"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Step 1: Updating system packages...${NC}"
sudo apt update
sudo apt upgrade -y

echo -e "${YELLOW}Step 2: Installing Python and dependencies...${NC}"
sudo apt install -y python3 python3-pip python3-venv

echo -e "${YELLOW}Step 3: Creating service directory...${NC}"
sudo mkdir -p $SERVICE_DIR
sudo mkdir -p $SERVICE_DIR/models
sudo mkdir -p $LOG_DIR

echo -e "${YELLOW}Step 4: Copying service files...${NC}"
sudo cp app.py $SERVICE_DIR/
sudo cp requirements.txt $SERVICE_DIR/
sudo cp README.md $SERVICE_DIR/

echo -e "${YELLOW}Step 5: Setting up Python virtual environment...${NC}"
cd $SERVICE_DIR
sudo python3 -m venv venv
sudo $SERVICE_DIR/venv/bin/pip install --upgrade pip
sudo $SERVICE_DIR/venv/bin/pip install -r requirements.txt

echo -e "${YELLOW}Step 6: Setting permissions...${NC}"
sudo chown -R $SERVICE_USER:$SERVICE_USER $SERVICE_DIR
sudo chown -R $SERVICE_USER:$SERVICE_USER $LOG_DIR
sudo chmod -R 755 $SERVICE_DIR
sudo chmod -R 755 $LOG_DIR

echo -e "${YELLOW}Step 7: Installing systemd service...${NC}"
sudo cp onnx-service.service /etc/systemd/system/
sudo systemctl daemon-reload

echo -e "${YELLOW}Step 8: Configuring firewall...${NC}"
sudo ufw allow 5000/tcp

echo -e "${YELLOW}Step 9: Starting service...${NC}"
sudo systemctl enable $SERVICE_NAME
sudo systemctl start $SERVICE_NAME

echo -e "${GREEN}======================================"
echo "Deployment Complete!"
echo "======================================${NC}"

echo ""
echo -e "${GREEN}Service Status:${NC}"
sudo systemctl status $SERVICE_NAME --no-pager

echo ""
echo -e "${YELLOW}Useful Commands:${NC}"
echo "  - Check status: sudo systemctl status $SERVICE_NAME"
echo "  - View logs: sudo journalctl -u $SERVICE_NAME -f"
echo "  - Restart: sudo systemctl restart $SERVICE_NAME"
echo "  - Stop: sudo systemctl stop $SERVICE_NAME"
echo ""
echo -e "${YELLOW}Test the service:${NC}"
echo "  curl http://localhost:5000/health"
echo "  curl http://pepcmmsengineering.my.id:5000/health"
echo ""
echo -e "${RED}Note: Place your ONNX model files in:${NC}"
echo "  $SERVICE_DIR/models/"
echo "  - chiller1_model.onnx"
echo "  - chiller2_model.onnx"
echo "  - compressor1_model.onnx"
echo "  - compressor2_model.onnx"
echo "  - ahu_model.onnx"
