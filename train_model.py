import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
import joblib
import pickle

# Generate training data (500 companies)
np.random.seed(42)
n_samples = 500

data = {
    'annual_revenue': np.random.exponential(1000000, n_samples) + 50000,
    'net_profit': np.random.normal(100000, 300000, n_samples),
    'revenue_growth_yoy': np.random.normal(0.2, 0.3, n_samples),
    'profit_margin': np.random.normal(0.1, 0.15, n_samples),
    'documents_count': np.random.poisson(3, n_samples) + 1,
    'company_age': np.random.randint(1, 10, n_samples),
    'funding_rounds': np.random.poisson(1.5, n_samples)
}

df = pd.DataFrame(data)

# Ensure realistic constraints
df['profit_margin'] = df['profit_margin'].clip(-0.5, 0.5)
df['revenue_growth_yoy'] = df['revenue_growth_yoy'].clip(-0.5, 1.5)

# Calculate target risk score (0-100)
df['risk_score'] = (
    50.0 -
    (df['net_profit'].clip(lower=0) / 10000) * 0.1 +
    (df['net_profit'].clip(upper=0) / 10000) * 0.3 -
    df['revenue_growth_yoy'] * 15 +
    (100 - df['profit_margin'] * 100) * 0.3 -
    df['documents_count'] * 2 -
    df['company_age'] * 1.5 -
    df['funding_rounds'] * 3
)

# Add noise and clip
df['risk_score'] += np.random.normal(0, 5, n_samples)
df['risk_score'] = df['risk_score'].clip(0, 100)

# Save training data
df.to_csv('ml_training_data.csv', index=False)
print(f"✅ Generated {n_samples} training samples")

# Prepare features and target
X = df.drop('risk_score', axis=1)
y = df['risk_score']

# Split data
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42
)

# Scale features
scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train)
X_test_scaled = scaler.transform(X_test)

# Train model
model = RandomForestRegressor(
    n_estimators=100,
    max_depth=10,
    min_samples_split=5,
    random_state=42
)

model.fit(X_train_scaled, y_train)

# Evaluate
train_score = model.score(X_train_scaled, y_train)
test_score = model.score(X_test_scaled, y_test)

print(f"📊 Training R²: {train_score:.3f}")
print(f"📊 Test R²: {test_score:.3f}")

# Save model and scaler
import os
os.makedirs('ml_model', exist_ok=True)

joblib.dump(model, 'ml_model/risk_model.pkl')
joblib.dump(scaler, 'ml_model/scaler.pkl')

# Save feature columns
with open('ml_model/feature_columns.pkl', 'wb') as f:
    pickle.dump(X.columns.tolist(), f)

print("✅ Model saved to ml_model/ directory")
print("✅ You can now start the Flask API with: python ml_api.py")