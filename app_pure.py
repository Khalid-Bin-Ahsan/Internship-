#!/usr/bin/env python3
"""
app_pure.py - Flask server for the enhanced pure-Python model.

Endpoints:
- GET  /health
- POST /predict    -> accepts JSON object (single) or list of objects -> returns prediction(s)
- POST /explain    -> returns per-feature contributions and probability

Run:
    python app_pure.py
"""

from flask import Flask, request, jsonify
from pathlib import Path
import json, math, platform
import sys

MODEL_FILE = Path("pipeline_pure_enhanced.json")
if not MODEL_FILE.exists():
    raise SystemExit("Model file pipeline_pure_enhanced.json not found. Run train_model_pure_enhanced.py first.")

model = json.loads(MODEL_FILE.read_text())
pp = model['preprocessing']
candidate_list = model['candidate_feature_list']
selected = model['selected_features']
col_means = model['scaling']['col_means']
col_stds = model['scaling']['col_stds']
weights_map = model['weights']
bias = model['bias']

# helper: create a per-request processed feature vector for selected features
def to_float_or_none(x):
    if x is None or x == '' or (isinstance(x,str) and x.lower()=='nan'):
        return None
    try:
        return float(x)
    except:
        return None

def winsorize(x, bounds):
    if x is None: return None
    l,u = bounds
    if l is None or u is None: return x
    if x < l: return l
    if x > u: return u
    return x

def log1p_safe(x):
    if x is None: return None
    try:
        return math.log1p(max(0.0, x))
    except:
        return None

# recreate derived functions same as training
def derived_values_for_row(r):
    # note: safe conversions
    def val(k): return to_float_or_none(r.get(k))
    out = {}
    try:
        out['revenue_per_founder'] = (val('annual_revenue') or 0.0) / max(1.0, float(val('num_founders') or 1.0))
    except:
        out['revenue_per_founder'] = 0.0
    try:
        out['debt_to_cash_ratio'] = (val('debt_equity_ratio') or 0.0) / (1.0 + abs(float(val('cash_balance') or 0.0)))
    except:
        out['debt_to_cash_ratio'] = 0.0
    try:
        out['revenue_to_total_raised'] = (val('annual_revenue') or 0.0) / (1.0 + abs(float(val('total_raised') or 0.0)))
    except:
        out['revenue_to_total_raised'] = 0.0
    try:
        out['profit_to_revenue'] = (val('net_profit') or 0.0) / (1.0 + abs(float(val('annual_revenue') or 0.0)))
    except:
        out['profit_to_revenue'] = 0.0
    return out

def preprocess_single(r):
    # 1) base features (winsorize + impute + log if requested)
    processed = {}
    for f in pp['base_features']:
        raw = to_float_or_none(r.get(f))
        # winsorize
        lb, ub = pp['iqr'].get(f, (None,None))
        raw = winsorize(raw, (lb,ub))
        if raw is None:
            raw = pp['medians'].get(f, 0.0)
            missing_ind = 1
        else:
            missing_ind = 0
        if f in pp['log_fields']:
            raw = log1p_safe(raw)
            if raw is None:
                raw = pp['medians'].get(f, 0.0)
        processed[f] = raw
        processed[f + "_missing"] = missing_ind
    # 2) derived
    derived = derived_values_for_row(r)
    for d,v in derived.items():
        lb, ub = pp['iqr'].get(d, (None,None))
        v = winsorize(v, (lb,ub))
        if v is None:
            v = pp['medians'].get(d, 0.0)
        processed[d] = v
    # 3) build scaled selected feature vector and also per-feature unscaled raw (for explain)
    feat_vector = []
    contribs = {}
    for fname in selected:
        # look up raw value from processed (candidate list includes missing indicators etc)
        raw_val = processed.get(fname, None)
        # if still None, fallback to median or zero
        if raw_val is None:
            raw_val = pp['medians'].get(fname, 0.0)
        # scale using col_means/stds (col_means keyed by candidate features)
        mean = col_means.get(fname, 0.0)
        std = col_stds.get(fname, 1.0)
        if std == 0: std = 1.0
        scaled = (raw_val - mean) / std
        feat_vector.append(scaled)
        weight = weights_map.get(fname, 0.0)
        contribs[fname] = {"raw": raw_val, "scaled": scaled, "weight": weight, "contribution": weight * scaled}
    return feat_vector, contribs

def sigmoid(z):
    if z >= 0:
        ez = math.exp(-z)
        return 1.0 / (1.0 + ez)
    else:
        ez = math.exp(z)
        return ez / (1.0 + ez)

app = Flask(__name__)

@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status":"ok","python":platform.python_version(),"model":"pipeline_pure_enhanced","selected_features": selected}), 200

@app.route("/predict", methods=["POST"])
def predict():
    data = request.get_json(force=True, silent=True)
    if data is None:
        return jsonify({"error":"missing json body"}), 400
    single = False
    if isinstance(data, dict):
        rows = [data]; single = True
    elif isinstance(data, list):
        rows = data
    else:
        return jsonify({"error":"json must be object or list"}), 400
    out = []
    for r in rows:
        x_vec, _ = preprocess_single(r)
        z = bias + sum(weights_map.get(f,0.0) * x_vec[i] for i,f in enumerate(selected))
        p = sigmoid(z)
        out.append({"prediction_default": int(p>=0.5), "probability_default": float(p)})
    return jsonify(out[0] if single else out), 200

@app.route("/explain", methods=["POST"])
def explain():
    data = request.get_json(force=True, silent=True)
    if data is None or (not isinstance(data, dict) and not isinstance(data, list)):
        return jsonify({"error":"missing json body (object or list expected)"}), 400
    single = False
    if isinstance(data, dict):
        rows = [data]; single = True
    else:
        rows = data
    outs = []
    for r in rows:
        x_vec, contribs = preprocess_single(r)
        # sum contributions
        total = bias + sum(contribs[f]["contribution"] for f in contribs)
        prob = sigmoid(total)
        # sort top contributions
        contrib_list = sorted([(f, contribs[f]['contribution'], contribs[f]['raw']) for f in contribs], key=lambda x: abs(x[1]), reverse=True)
        outs.append({
            "probability_default": float(prob),
            "bias": float(bias),
            "feature_contributions": [{"feature":f, "contribution":c, "raw": rraw} for f,c,rraw in contrib_list]
        })
    return jsonify(outs[0] if single else outs), 200

if __name__ == "__main__":
    print("Starting ML Flask server (pure Python) ...")
    app.run(host="0.0.0.0", port=5001)
