"""
Test script for ONNX Prediction Service
Usage: python test_service.py
"""

import requests
import json

# Configuration
BASE_URL = "http://pepcmmsengineering.my.id:5000"
# BASE_URL = "http://localhost:5000"

def test_health():
    """Test health check endpoint"""
    print("\n" + "="*50)
    print("Testing Health Check...")
    print("="*50)
    
    try:
        response = requests.get(f"{BASE_URL}/health", timeout=5)
        print(f"Status Code: {response.status_code}")
        print(f"Response: {json.dumps(response.json(), indent=2)}")
        return response.status_code == 200
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        return False

def test_models():
    """Test list models endpoint"""
    print("\n" + "="*50)
    print("Testing List Models...")
    print("="*50)
    
    try:
        response = requests.get(f"{BASE_URL}/models", timeout=5)
        print(f"Status Code: {response.status_code}")
        print(f"Response: {json.dumps(response.json(), indent=2)}")
        return response.status_code == 200
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        return False

def test_chiller1_prediction():
    """Test chiller1 prediction"""
    print("\n" + "="*50)
    print("Testing Chiller 1 Prediction...")
    print("="*50)
    
    payload = {
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
    
    try:
        response = requests.post(
            f"{BASE_URL}/predict",
            json=payload,
            headers={"Content-Type": "application/json"},
            timeout=30
        )
        print(f"Status Code: {response.status_code}")
        print(f"Response: {json.dumps(response.json(), indent=2)}")
        return response.status_code == 200
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        return False

def test_compressor_prediction():
    """Test compressor prediction"""
    print("\n" + "="*50)
    print("Testing Compressor 1 Prediction...")
    print("="*50)
    
    payload = {
        "equipment_type": "compressor1",
        "features": {
            "suction_pressure": 3.8,
            "discharge_pressure": 15.2,
            "oil_pressure": 2.5,
            "suction_temp": 12.5,
            "discharge_temp": 92.3,
            "oil_temp": 65.4,
            "motor_current": 45.2,
            "vibration": 2.1
        }
    }
    
    try:
        response = requests.post(
            f"{BASE_URL}/predict",
            json=payload,
            headers={"Content-Type": "application/json"},
            timeout=30
        )
        print(f"Status Code: {response.status_code}")
        print(f"Response: {json.dumps(response.json(), indent=2)}")
        return response.status_code == 200
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        return False

def test_ahu_prediction():
    """Test AHU prediction"""
    print("\n" + "="*50)
    print("Testing AHU Prediction...")
    print("="*50)
    
    payload = {
        "equipment_type": "ahu",
        "features": {
            "supply_temp": 18.5,
            "return_temp": 24.3,
            "filter_pressure_drop": 125.0,
            "fan_speed": 1450,
            "humidity": 55.2,
            "motor_current": 12.5
        }
    }
    
    try:
        response = requests.post(
            f"{BASE_URL}/predict",
            json=payload,
            headers={"Content-Type": "application/json"},
            timeout=30
        )
        print(f"Status Code: {response.status_code}")
        print(f"Response: {json.dumps(response.json(), indent=2)}")
        return response.status_code == 200
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        return False

def run_all_tests():
    """Run all tests"""
    print("\n" + "="*60)
    print("ONNX Prediction Service Test Suite")
    print(f"Target: {BASE_URL}")
    print("="*60)
    
    results = {
        "Health Check": test_health(),
        "List Models": test_models(),
        "Chiller 1 Prediction": test_chiller1_prediction(),
        "Compressor 1 Prediction": test_compressor_prediction(),
        "AHU Prediction": test_ahu_prediction()
    }
    
    print("\n" + "="*60)
    print("Test Results Summary")
    print("="*60)
    
    for test_name, passed in results.items():
        status = "✅ PASSED" if passed else "❌ FAILED"
        print(f"{test_name}: {status}")
    
    total_tests = len(results)
    passed_tests = sum(results.values())
    
    print("\n" + "="*60)
    print(f"Total: {passed_tests}/{total_tests} tests passed")
    print("="*60)

if __name__ == "__main__":
    run_all_tests()
