from flask import Flask, request, jsonify
from flask_cors import CORS
import pickle
import numpy as np
import pandas as pd
import joblib
from datetime import datetime

app = Flask(__name__)
CORS(app)

# Load your trained ML model (you need to train this first)
model = None
scaler = None
feature_columns = None

def load_model():
    global model, scaler, feature_columns
    try:
        model = joblib.load('ml_model/risk_model.pkl')
        scaler = joblib.load('ml_model/scaler.pkl')
        with open('ml_model/feature_columns.pkl', 'rb') as f:
            feature_columns = pickle.load(f)
        print("✅ ML Model loaded successfully")
    except:
        print("⚠️ No trained model found. Using fallback calculation.")
        model = None

load_model()

def predict_with_model(data):
    """Predict using trained ML model"""
    if model is None:
        return None
    
    # Convert to DataFrame
    df = pd.DataFrame([data])
    
    # Ensure all columns exist
    for col in feature_columns:
        if col not in df.columns:
            df[col] = 0
    
    # Reorder columns
    df = df[feature_columns]
    
    # Scale features
    scaled_features = scaler.transform(df)
    
    # Predict
    raw_score = model.predict(scaled_features)[0]
    
    # Ensure score is between 0-100
    risk_score = max(0, min(100, float(raw_score)))
    
    return risk_score

def calculate_risk_level(score):
    """Convert numerical score to risk level"""
    if score >= 80:
        return "Very High Risk", "#ef4444"
    elif score >= 60:
        return "High Risk", "#f97316"
    elif score >= 40:
        return "Moderate Risk", "#fbbf24"
    elif score >= 20:
        return "Low Risk", "#22c55e"
    else:
        return "Very Low Risk", "#10b981"

@app.route('/predict', methods=['POST'])
def predict():
    try:
        data = request.json
        
        # Extract features
        features = {
            'annual_revenue': float(data.get('annual_revenue', 0)),
            'net_profit': float(data.get('net_profit', 0)),
            'revenue_growth_yoy': float(data.get('revenue_growth_yoy', 0)),
            'profit_margin': float(data.get('profit_margin', 0)),
            'documents_count': int(data.get('documents_count', 0)),
            'company_age': int(data.get('company_age', 1)),
            'funding_rounds': int(data.get('funding_rounds', 0))
        }
        
        # Try ML model prediction first
        risk_score = predict_with_model(features)
        
        if risk_score is None:
            # Fallback calculation if model not trained
            risk_score = calculate_fallback_score(features)
        
        # Calculate risk level
        risk_level, color = calculate_risk_level(risk_score)
        
        # Calculate breakdown scores
        breakdown = calculate_breakdown_scores(features)
        
        return jsonify({
            'success': True,
            'risk_score': round(risk_score, 2),
            'risk_level': risk_level,
            'color': color,
            'breakdown': breakdown,
            'model_used': 'ml' if model is not None else 'fallback',
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 400

def calculate_fallback_score(features):
    """Fallback calculation when ML model is not available"""
    score = 50.0  # Base score
    
    # Adjust based on features (similar to PHP fallback)
    if features['net_profit'] > 0:
        score -= 15
    if features['revenue_growth_yoy'] > 0.2:
        score -= 10
    if features['profit_margin'] > 0.1:
        score -= 10
    if features['documents_count'] > 0:
        score -= 5
    if features['company_age'] > 5:
        score -= 5
    if features['funding_rounds'] > 0:
        score -= 3
    
    return max(0, min(100, score))

def calculate_breakdown_scores(features):
    """Calculate individual risk component scores"""
    financial_health = max(0, min(100, 100 - (abs(features['net_profit']) / max(features['annual_revenue'], 1)) * 100))
    growth_potential = max(0, min(100, features['revenue_growth_yoy'] * 200))
    market_risk = max(0, min(100, 50 - features['profit_margin'] * 100))
    operational_risk = max(0, min(100, 100 - features['documents_count'] * 20))
    
    return {
        'financial_health': round(financial_health, 2),
        'growth_potential': round(growth_potential, 2),
        'market_risk': round(market_risk, 2),
        'operational_risk': round(operational_risk, 2)
    }

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'status': 'healthy',
        'model_loaded': model is not None,
        'timestamp': datetime.now().isoformat()
    })

if __name__ == '__main__':
    print("🚀 Starting ML API Server on http://localhost:5000")
    app.run(host='0.0.0.0', port=5000, debug=True)