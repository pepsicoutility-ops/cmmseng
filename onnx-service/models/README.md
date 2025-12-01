# ONNX Model Files Directory

Place your trained ONNX model files here:

## Required Models:

1. **chiller1_model.onnx** - Chiller 1 anomaly detection model
2. **chiller2_model.onnx** - Chiller 2 anomaly detection model  
3. **compressor1_model.onnx** - Compressor 1 anomaly detection model
4. **compressor2_model.onnx** - Compressor 2 anomaly detection model
5. **ahu_model.onnx** - AHU anomaly detection model

## Model Training Notes:

### Input Features per Equipment Type:

**Chillers (11 features):**
- evap_p, conds_p, oil_p
- evap_t, suct_t, disc_t
- sub_cooling, super_heating
- cond_water_in_temp, cond_water_out_temp
- cooler_chorus_small_temp_diff

**Compressors (8 features):**
- suction_pressure, discharge_pressure, oil_pressure
- suction_temp, discharge_temp, oil_temp
- motor_current, vibration

**AHU (6 features):**
- supply_temp, return_temp
- filter_pressure_drop, fan_speed
- humidity, motor_current

### Expected Output:
- Binary classification (normal/anomaly)
- Output shape: (1, 2) for probability or (1,) for single value
- Values between 0.0 and 1.0

### Model Export Example (scikit-learn):

```python
from skl2onnx import convert_sklearn
from skl2onnx.common.data_types import FloatTensorType

# Define input type (adjust n_features for each equipment)
initial_type = [('float_input', FloatTensorType([None, 11]))]

# Convert model
onnx_model = convert_sklearn(trained_model, initial_types=initial_type)

# Save
with open("chiller1_model.onnx", "wb") as f:
    f.write(onnx_model.SerializeToString())
```

## Testing Models:

After placing models, verify they load correctly:

```bash
curl http://localhost:5000/models
```

Or use the test script:

```bash
python test_service.py
```
