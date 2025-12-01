# ONNX Prediction Service

Flask-based REST API for serving ONNX machine learning models for equipment anomaly detection.

## Features

- 🚀 Fast inference with ONNX Runtime
- 🔄 Support for 5 equipment types (Chiller1, Chiller2, Compressor1, Compressor2, AHU)
- 📊 Feature importance calculation
- 🛡️ Error handling and fallback responses
- 📝 Comprehensive logging
- 🌐 CORS enabled for Laravel integration

## Installation

### 1. Install Python 3.8+

```bash
python3 --version
```

### 2. Create Virtual Environment

```bash
cd onnx-service
python3 -m venv venv
source venv/bin/activate  # On Linux/Mac
# OR
venv\Scripts\activate  # On Windows
```

### 3. Install Dependencies

```bash
pip install -r requirements.txt
```

## Directory Structure

```
onnx-service/
├── app.py                 # Main Flask application
├── requirements.txt       # Python dependencies
├── models/               # ONNX model files
│   ├── chiller1_model.onnx
│   ├── chiller2_model.onnx
│   ├── compressor1_model.onnx
│   ├── compressor2_model.onnx
│   └── ahu_model.onnx
└── README.md
```

## Running the Service

### Development Mode

```bash
python app.py
```

### Production Mode (with Gunicorn)

```bash
gunicorn -w 4 -b 0.0.0.0:5000 app:app
```

## API Endpoints

### Health Check
```http
GET /health
```

Response:
```json
{
  "status": "healthy",
  "service": "ONNX Prediction Service",
  "version": "1.0.0"
}
```

### Predict
```http
POST /predict
Content-Type: application/json
```

Request:
```json
{
  "equipment_type": "chiller1",
  "features": {
    "evap_p": 4.5,
    "conds_p": 12.3,
    "oil_p": 2.1,
    "evap_t": 6.5,
    "suct_t": 8.2,
    "disc_t": 85.3,
    "sub_cooling": 4.5,
    "super_heating": 5.8,
    "cond_water_in_temp": 30.5,
    "cond_water_out_temp": 35.2,
    "cooler_chorus_small_temp_diff": 4.7
  }
}
```

Response:
```json
{
  "is_anomaly": true,
  "risk_signal": "high",
  "raw_label": "anomaly_detected",
  "confidence_score": 87.5,
  "feature_importance": {
    "evap_p": 0.35,
    "conds_p": 0.28,
    "disc_t": 0.15,
    "oil_p": 0.12,
    "evap_t": 0.10
  }
}
```

### List Models
```http
GET /models
```

Response:
```json
{
  "chiller1": {
    "path": "/path/to/models/chiller1_model.onnx",
    "exists": true,
    "loaded": true,
    "features": ["evap_p", "conds_p", ...]
  }
}
```

## Equipment Types & Features

### Chiller 1 & 2
- evap_p (Evaporator Pressure)
- conds_p (Condenser Pressure)
- oil_p (Oil Pressure)
- evap_t (Evaporator Temperature)
- suct_t (Suction Temperature)
- disc_t (Discharge Temperature)
- sub_cooling (Subcooling)
- super_heating (Superheating)
- cond_water_in_temp (Condenser Water Inlet)
- cond_water_out_temp (Condenser Water Outlet)
- cooler_chorus_small_temp_diff (Temperature Difference)

### Compressor 1 & 2
- suction_pressure
- discharge_pressure
- oil_pressure
- suction_temp
- discharge_temp
- oil_temp
- motor_current
- vibration

### AHU (Air Handling Unit)
- supply_temp
- return_temp
- filter_pressure_drop
- fan_speed
- humidity
- motor_current

## Deployment on VPS

### 1. Upload Files to VPS

```bash
scp -r onnx-service user@pepcmmsengineering.my.id:/home/user/
```

### 2. Install System Dependencies

```bash
ssh user@pepcmmsengineering.my.id
sudo apt update
sudo apt install python3 python3-pip python3-venv -y
```

### 3. Set Up Service

```bash
cd /home/user/onnx-service
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### 4. Create Systemd Service

Create `/etc/systemd/system/onnx-service.service`:

```ini
[Unit]
Description=ONNX Prediction Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/home/user/onnx-service
Environment="PATH=/home/user/onnx-service/venv/bin"
ExecStart=/home/user/onnx-service/venv/bin/gunicorn -w 4 -b 0.0.0.0:5000 app:app
Restart=always

[Install]
WantedBy=multi-user.target
```

### 5. Start Service

```bash
sudo systemctl daemon-reload
sudo systemctl enable onnx-service
sudo systemctl start onnx-service
sudo systemctl status onnx-service
```

### 6. Configure Firewall

```bash
sudo ufw allow 5000/tcp
```

## Training ONNX Models

Your ML models should be exported to ONNX format. Example with scikit-learn:

```python
from skl2onnx import convert_sklearn
from skl2onnx.common.data_types import FloatTensorType

# Train your model
model = RandomForestClassifier()
model.fit(X_train, y_train)

# Convert to ONNX
initial_type = [('float_input', FloatTensorType([None, 11]))]
onnx_model = convert_sklearn(model, initial_types=initial_type)

# Save
with open("models/chiller1_model.onnx", "wb") as f:
    f.write(onnx_model.SerializeToString())
```

## Testing

```bash
# Test health endpoint
curl http://pepcmmsengineering.my.id:5000/health

# Test prediction
curl -X POST http://pepcmmsengineering.my.id:5000/predict \
  -H "Content-Type: application/json" \
  -d '{
    "equipment_type": "chiller1",
    "features": {
      "evap_p": 4.5,
      "conds_p": 12.3,
      "oil_p": 2.1,
      "evap_t": 6.5,
      "suct_t": 8.2,
      "disc_t": 85.3,
      "sub_cooling": 4.5,
      "super_heating": 5.8,
      "cond_water_in_temp": 30.5,
      "cond_water_out_temp": 35.2,
      "cooler_chorus_small_temp_diff": 4.7
    }
  }'
```

## Troubleshooting

### Service won't start
```bash
sudo journalctl -u onnx-service -f
```

### Port already in use
```bash
sudo lsof -i :5000
sudo kill -9 <PID>
```

### Model loading errors
- Ensure ONNX models are in `models/` directory
- Check file permissions: `chmod 644 models/*.onnx`
- Verify ONNX model format is compatible with onnxruntime version

## Performance Tips

- Use Gunicorn with multiple workers (4-8 recommended)
- Consider using Nginx as reverse proxy
- Enable model caching (already implemented)
- Monitor memory usage with large models
- Use GPU inference for faster predictions (requires onnxruntime-gpu)

## Security

- Run service behind Nginx reverse proxy
- Enable HTTPS/TLS
- Implement API authentication (JWT/API keys)
- Rate limiting
- Input validation

## License

Proprietary - CMMS Engineering System
