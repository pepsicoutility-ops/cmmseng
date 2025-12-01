"""
ONNX ML Prediction Service for CMMS Equipment Monitoring
Flask API for serving ONNX models for anomaly detection
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import numpy as np
import onnxruntime as ort
import logging
from pathlib import Path

app = Flask(__name__)
CORS(app)

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Model paths
MODEL_DIR = Path(__file__).parent / 'models'
MODELS = {
    'chiller1': MODEL_DIR / 'chiller1_model.onnx',
    'chiller2': MODEL_DIR / 'chiller2_model.onnx',
    'compressor1': MODEL_DIR / 'compressor1_model.onnx',
    'compressor2': MODEL_DIR / 'compressor2_model.onnx',
    'ahu': MODEL_DIR / 'ahu_model.onnx'
}

# Feature configurations for each equipment type
FEATURE_CONFIGS = {
    'chiller1': [
        'evap_p', 'conds_p', 'oil_p', 'evap_t', 'suct_t', 'disc_t',
        'sub_cooling', 'super_heating', 'cond_water_in_temp',
        'cond_water_out_temp', 'cooler_chorus_small_temp_diff'
    ],
    'chiller2': [
        'evap_p', 'conds_p', 'oil_p', 'evap_t', 'suct_t', 'disc_t',
        'sub_cooling', 'super_heating', 'cond_water_in_temp',
        'cond_water_out_temp', 'cooler_chorus_small_temp_diff'
    ],
    'compressor1': [
        'suction_pressure', 'discharge_pressure', 'oil_pressure',
        'suction_temp', 'discharge_temp', 'oil_temp',
        'motor_current', 'vibration'
    ],
    'compressor2': [
        'suction_pressure', 'discharge_pressure', 'oil_pressure',
        'suction_temp', 'discharge_temp', 'oil_temp',
        'motor_current', 'vibration'
    ],
    'ahu': [
        'supply_temp', 'return_temp', 'filter_pressure_drop',
        'fan_speed', 'humidity', 'motor_current'
    ]
}

# Load ONNX models (lazy loading)
loaded_models = {}

def load_model(equipment_type):
    """Load ONNX model for specific equipment type"""
    if equipment_type in loaded_models:
        return loaded_models[equipment_type]
    
    model_path = MODELS.get(equipment_type)
    if not model_path or not model_path.exists():
        logger.warning(f"Model not found for {equipment_type} at {model_path}")
        return None
    
    try:
        session = ort.InferenceSession(str(model_path))
        loaded_models[equipment_type] = session
        logger.info(f"Loaded model for {equipment_type}")
        return session
    except Exception as e:
        logger.error(f"Error loading model for {equipment_type}: {str(e)}")
        return None

def preprocess_features(features, equipment_type):
    """Extract and order features according to model requirements"""
    feature_names = FEATURE_CONFIGS.get(equipment_type, [])
    
    # Extract features in correct order
    feature_values = []
    for fname in feature_names:
        value = features.get(fname, 0.0)
        # Handle None values
        if value is None:
            value = 0.0
        feature_values.append(float(value))
    
    # Convert to numpy array with shape (1, n_features)
    return np.array([feature_values], dtype=np.float32)

def calculate_feature_importance(features, prediction, equipment_type):
    """Calculate feature importance (simplified version)"""
    feature_names = FEATURE_CONFIGS.get(equipment_type, [])
    
    # Simple importance calculation based on feature magnitude
    # In production, use SHAP values or model-specific importance
    feature_values = [abs(float(features.get(f, 0.0))) for f in feature_names]
    total = sum(feature_values) or 1.0
    
    importance = {}
    for fname, value in zip(feature_names, feature_values):
        importance[fname] = round(value / total, 4)
    
    # Sort by importance and return top features
    sorted_importance = dict(sorted(importance.items(), key=lambda x: x[1], reverse=True))
    return sorted_importance

def interpret_prediction(output, equipment_type):
    """Interpret model output into structured response"""
    # This is a placeholder interpretation
    # Adjust based on your actual model output format
    
    # Assuming binary classification with probability output
    if len(output.shape) > 1:
        anomaly_prob = float(output[0][1]) if output.shape[1] > 1 else float(output[0][0])
    else:
        anomaly_prob = float(output[0])
    
    # Determine anomaly status
    is_anomaly = anomaly_prob > 0.5
    
    # Determine risk signal based on probability
    if anomaly_prob >= 0.9:
        risk_signal = 'critical'
    elif anomaly_prob >= 0.7:
        risk_signal = 'high'
    elif anomaly_prob >= 0.5:
        risk_signal = 'medium'
    else:
        risk_signal = 'low'
    
    # Generate raw label
    if is_anomaly:
        if anomaly_prob >= 0.8:
            raw_label = 'critical_anomaly'
        else:
            raw_label = 'anomaly_detected'
    else:
        raw_label = 'normal_operation'
    
    return {
        'is_anomaly': is_anomaly,
        'risk_signal': risk_signal,
        'raw_label': raw_label,
        'confidence_score': round(anomaly_prob * 100, 2)
    }

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'ONNX Prediction Service',
        'version': '1.0.0'
    })

@app.route('/predict', methods=['POST'])
def predict():
    """Main prediction endpoint"""
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({'error': 'No data provided'}), 400
        
        equipment_type = data.get('equipment_type')
        features = data.get('features', {})
        
        if not equipment_type:
            return jsonify({'error': 'equipment_type is required'}), 400
        
        if equipment_type not in MODELS:
            return jsonify({'error': f'Unknown equipment type: {equipment_type}'}), 400
        
        logger.info(f"Prediction request for {equipment_type}")
        
        # Load model
        model = load_model(equipment_type)
        
        # If model not available, return mock prediction
        if model is None:
            logger.warning(f"Model not available, returning mock prediction")
            return jsonify({
                'is_anomaly': False,
                'risk_signal': 'low',
                'raw_label': 'model_not_loaded',
                'confidence_score': 0.0,
                'feature_importance': {},
                'note': 'Model not available, mock prediction returned'
            })
        
        # Preprocess features
        input_data = preprocess_features(features, equipment_type)
        
        # Get input name from model
        input_name = model.get_inputs()[0].name
        
        # Run inference
        outputs = model.run(None, {input_name: input_data})
        prediction = outputs[0]
        
        # Interpret prediction
        result = interpret_prediction(prediction, equipment_type)
        
        # Calculate feature importance
        result['feature_importance'] = calculate_feature_importance(
            features, 
            prediction, 
            equipment_type
        )
        
        logger.info(f"Prediction successful: {result['risk_signal']}")
        return jsonify(result)
        
    except Exception as e:
        logger.error(f"Prediction error: {str(e)}", exc_info=True)
        return jsonify({
            'error': 'Prediction failed',
            'message': str(e)
        }), 500

@app.route('/models', methods=['GET'])
def list_models():
    """List available models"""
    available_models = {}
    for eq_type, model_path in MODELS.items():
        available_models[eq_type] = {
            'path': str(model_path),
            'exists': model_path.exists(),
            'loaded': eq_type in loaded_models,
            'features': FEATURE_CONFIGS.get(eq_type, [])
        }
    return jsonify(available_models)

if __name__ == '__main__':
    # Create models directory if not exists
    MODEL_DIR.mkdir(exist_ok=True)
    
    logger.info("Starting ONNX Prediction Service...")
    logger.info(f"Model directory: {MODEL_DIR}")
    
    # Run Flask app
    app.run(
        host='0.0.0.0',
        port=5000,
        debug=False
    )
