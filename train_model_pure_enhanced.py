#!/usr/bin/env python3
"""
train_model_pure_enhanced.py

Pure-Python training pipeline (no numpy/pandas) for Python 3.14:
- Loads CSV: blocksight_companies_500.csv (in same folder)
- Preprocessing:
    * median imputation
    * missing indicator features
    * IQR winsorization (outlier capping)
    * log1p transform for skewed financial fields
    * derived features (ratios)
- Feature selection: univariate Pearson correlation vs binary target (top-K)
- Train logistic regression (batch GD) with L2 regularization + early stopping
- Save model artifact as pipeline_pure_enhanced.json

Run:
    python train_model_pure_enhanced.py
"""

from pathlib import Path
import csv, math, json, random, statistics, sys
from typing import List, Dict

CSV_FILE = "blocksight_companies_500.csv"
OUT_MODEL = "pipeline_pure_enhanced.json"
RANDOM_SEED = 42

# base numeric fields expected in CSV (these should match dataset columns)
BASE_FIELDS = [
    'annual_revenue','revenue_growth_yoy','net_profit','profit_margin',
    'current_ratio','debt_equity_ratio','cash_balance','burn_rate_monthly',
    'runway_months','previous_rounds','total_raised','founder_experience_years',
    'num_founders','has_audited_financials','monthly_active_users','customer_count',
    'churn_rate','market_growth_pct','competition_index','country_risk_score',
    'onchain_event_count','documents_uploaded_count','kyc_verified','investor_interest_score'
]
TARGET = "label_default_12m"

# Features we will log-transform because they are skewed (financials)
LOG_FIELDS = ['annual_revenue','cash_balance','total_raised','monthly_active_users','customer_count']

# Ratio/derived features to create (as name: lambda(row) -> numeric)
DERIVED_FEATURES = {
    # revenue per founder (guard for zero founders)
    'revenue_per_founder': lambda r: (to_float(r.get('annual_revenue')) / max(1.0, to_float(r.get('num_founders')))),
    'debt_to_cash_ratio': lambda r: (to_float(r.get('debt_equity_ratio')) / (1.0 + abs(to_float(r.get('cash_balance'))))),
    'revenue_to_total_raised': lambda r: (to_float(r.get('annual_revenue')) / (1.0 + abs(to_float(r.get('total_raised'))))),
    'profit_to_revenue': lambda r: (to_float(r.get('net_profit')) / (1.0 + abs(to_float(r.get('annual_revenue')))))
}

# number of top features to keep after univariate selection
TOP_K = 18

# -------------------------
# Utilities
# -------------------------
def to_float(x):
    if x is None or x == '' or (isinstance(x, str) and x.lower() == 'nan'):
        return None
    try:
        return float(x)
    except:
        return None

def read_csv(path: str) -> List[Dict[str,str]]:
    p = Path(path)
    if not p.exists():
        raise FileNotFoundError(f"{path} not found")
    with p.open(newline='', encoding='utf-8') as fh:
        reader = csv.DictReader(fh)
        rows = [r for r in reader]
    return rows

def column_values(rows, col):
    return [to_float(r.get(col)) for r in rows]

def median_nonnull(values):
    v = [x for x in values if x is not None]
    if not v:
        return 0.0
    return statistics.median(v)

def mean_nonnull(values):
    v = [x for x in values if x is not None]
    if not v:
        return 0.0
    return statistics.mean(v)

def std_nonnull(values):
    v = [x for x in values if x is not None]
    if len(v) < 2:
        return 1.0
    return statistics.pstdev(v)  # population stdev is fine for scaling

def iqr_bounds(values):
    v = sorted([x for x in values if x is not None])
    if len(v) < 4:
        return (min(v) if v else 0.0, max(v) if v else 0.0)
    q1_idx = int(0.25 * (len(v)-1))
    q3_idx = int(0.75 * (len(v)-1))
    q1 = v[q1_idx]; q3 = v[q3_idx]
    iqr = q3 - q1
    lower = q1 - 1.5 * iqr
    upper = q3 + 1.5 * iqr
    return (lower, upper)

def winsorize_value(x, lower, upper):
    if x is None:
        return None
    if x < lower: return lower
    if x > upper: return upper
    return x

def log1p_safe(x):
    if x is None:
        return None
    try:
        return math.log1p(max(0.0, x))
    except:
        return None

# -------------------------
# Preprocessing pipeline builder
# -------------------------
def build_preprocessing(rows):
    """Compute medians, means, stds, iqr bounds and which features exist"""
    feats = []
    for f in BASE_FIELDS:
        # include only fields present in CSV header
        if f in rows[0]:
            feats.append(f)
    # derived features may depend on base fields but we'll include them regardless
    derived = list(DERIVED_FEATURES.keys())

    # compute medians and iqr bounds for base and derived (eval derived on rows)
    medians = {}
    means = {}
    stds = {}
    iqr = {}
    # precompute derived raw values
    derived_vals = {d: [] for d in derived}
    for r in rows:
        for d,fn in DERIVED_FEATURES.items():
            try:
                derived_vals[d].append(fn(r))
            except:
                derived_vals[d].append(None)

    for f in feats:
        vals = column_values(rows, f)
        medians[f] = median_nonnull(vals)
        means[f] = mean_nonnull([v for v in vals if v is not None])
        stds[f] = std_nonnull([v for v in vals if v is not None])
        iqr[f] = iqr_bounds(vals)

    for d in derived:
        medians[d] = median_nonnull(derived_vals[d])
        means[d] = mean_nonnull([v for v in derived_vals[d] if v is not None])
        stds[d] = std_nonnull([v for v in derived_vals[d] if v is not None])
        iqr[d] = iqr_bounds(derived_vals[d])

    # detect final feature list: base + derived + missing indicators (for each base feature)
    final_features = []
    for f in feats:
        final_features.append(f)
        final_features.append(f + "_missing")  # indicator
    for d in derived:
        final_features.append(d)

    # record which fields will be log-transformed (intersection)
    log_fields = [f for f in LOG_FIELDS if f in feats]

    return {
        "base_features": feats,
        "derived_features": derived,
        "medians": medians,
        "means": means,
        "stds": stds,
        "iqr": iqr,
        "final_feature_list": final_features,
        "log_fields": log_fields
    }

def preprocess_row(r, pp):
    """Given raw CSV row dict, return a dict of preprocessed numeric values for each final feature."""
    out = {}
    # apply winsorize and impute for base features
    for f in pp['base_features']:
        raw = to_float(r.get(f))
        lower, upper = pp['iqr'].get(f, (None,None))
        if lower is not None and upper is not None:
            raw = winsorize_value(raw, lower, upper)
        if raw is None:
            out_val = pp['medians'][f]
            out[f + "_missing"] = 1
        else:
            out_val = raw
            out[f + "_missing"] = 0
        # log transform if configured
        if f in pp['log_fields']:
            out_val = log1p_safe(out_val)
            if out_val is None:
                out_val = pp['medians'][f]  # fallback
        out[f] = out_val

    # derived features
    for d in pp['derived_features']:
        try:
            v = DERIVED_FEATURES[d](r)
        except:
            v = None
        # winsorize derived using stored bounds
        lower, upper = pp['iqr'].get(d, (None,None))
        if lower is not None and upper is not None:
            v = winsorize_value(v, lower, upper)
        if v is None:
            v = pp['medians'][d]
        out[d] = v

    # At this stage we have raw numeric values; next step (in training) will scale using means/stds
    return out

# -------------------------
# Simple univariate selection: Pearson corr with binary target
# -------------------------
def pearson_corr(xs, ys):
    # xs,ys lists same length; handle missing as imputed before calling
    n = len(xs)
    if n < 2:
        return 0.0
    mean_x = statistics.mean(xs)
    mean_y = statistics.mean(ys)
    num = sum((xs[i]-mean_x)*(ys[i]-mean_y) for i in range(n))
    denx = math.sqrt(sum((xs[i]-mean_x)**2 for i in range(n)))
    deny = math.sqrt(sum((ys[i]-mean_y)**2 for i in range(n)))
    if denx == 0 or deny == 0:
        return 0.0
    return num / (denx * deny)

# -------------------------
# Logistic regression (batch GD)
# -------------------------
def sigmoid(z):
    if z >= 0:
        ez = math.exp(-z)
        return 1.0 / (1.0 + ez)
    else:
        ez = math.exp(z)
        return ez / (1.0 + ez)

def train_logreg(X, y, lr=0.05, epochs=5000, l2=1e-3, early_stop_rounds=200):
    # X: list of lists (n x m), y: list of {0,1}
    n = len(X)
    m = len(X[0])
    random.seed(RANDOM_SEED)
    weights = [random.uniform(-0.01,0.01) for _ in range(m)]
    bias = 0.0
    best_loss = float('inf'); noimp=0
    for epoch in range(epochs):
        grad_w = [0.0]*m; grad_b = 0.0
        loss = 0.0
        for i in range(n):
            xi = X[i]; yi = y[i]
            z = bias + sum(weights[j]*xi[j] for j in range(m))
            p = sigmoid(z)
            p_clip = min(max(p,1e-12), 1-1e-12)
            loss += - (yi*math.log(p_clip) + (1-yi)*math.log(1-p_clip))
            err = p - yi
            for j in range(m):
                grad_w[j] += err * xi[j]
            grad_b += err
        # avg + reg
        for j in range(m):
            grad_w[j] = grad_w[j]/n + l2*weights[j]
        grad_b = grad_b / n
        for j in range(m):
            weights[j] -= lr * grad_w[j]
        bias -= lr * grad_b
        loss = loss / n + 0.5 * l2 * sum(w*w for w in weights)
        if loss + 1e-12 < best_loss:
            best_loss = loss; noimp = 0
        else:
            noimp += 1
            if noimp >= early_stop_rounds:
                print(f"[train] early stopping at epoch {epoch}, loss {loss:.6f}")
                break
        if epoch % 200 == 0 or epoch==epochs-1:
            print(f"[train] epoch {epoch} loss={loss:.6f}")
    return weights, bias

# -------------------------
# End-to-end training
# -------------------------
def main():
    print("Loading CSV...")
    rows = read_csv(CSV_FILE)
    if not rows:
        print("No rows found, abort.")
        return
    # Build preprocessing metadata
    print("Building preprocessing metadata...")
    pp = build_preprocessing(rows)

    # Build dataset: for each row, create preprocessed raw features (imputed/winsorized/logified)
    print("Preprocessing rows and collecting feature matrix...")
    feature_raw_names = []  # will contain final candidate features before selection
    # candidate features = base fields + missing indicators + derived
    for f in pp['base_features']:
        feature_raw_names.append(f)
        feature_raw_names.append(f + "_missing")
    for d in pp['derived_features']:
        feature_raw_names.append(d)

    X_raw = []  # rows of raw (not yet scaled) numeric values aligned with feature_raw_names
    y = []
    for r in rows:
        # skip rows with no target
        t = to_float(r.get(TARGET))
        if t is None:
            continue
        processed = preprocess_row(r, pp)
        rowvals = []
        for fname in feature_raw_names:
            v = processed.get(fname)
            # some missing indicators might be absent if base field missing from CSV header -> treat as 0
            if v is None:
                v = 0.0
            rowvals.append(float(v))
        X_raw.append(rowvals)
        y.append(1 if float(t) != 0.0 else 0)
    print(f"Prepared {len(X_raw)} samples with {len(feature_raw_names)} candidate features.")

    # Scale features (compute means/stds on X_raw columns)
    print("Computing scaling parameters...")
    m = len(feature_raw_names)
    col_means = []
    col_stds = []
    for j in range(m):
        col = [X_raw[i][j] for i in range(len(X_raw))]
        col_means.append(mean_nonnull(col))
        s = std_nonnull(col)
        if s == 0:
            s = 1.0
        col_stds.append(s)

    # create scaled matrix X_scaled
    X_scaled = []
    for row in X_raw:
        scaled = [(row[j] - col_means[j]) / col_stds[j] for j in range(m)]
        X_scaled.append(scaled)

    # Feature selection: compute Pearson corr between each scaled feature and y
    print("Running univariate correlation feature selection...")
    corrs = []
    for j in range(m):
        xs = [X_scaled[i][j] for i in range(len(X_scaled))]
        corr = pearson_corr(xs, y)
        corrs.append((feature_raw_names[j], abs(corr), corr))
    corrs_sorted = sorted(corrs, key=lambda x: x[1], reverse=True)
    selected = [name for name,absval,c in corrs_sorted[:TOP_K]]
    print("Top selected features:", selected)

    # Build final X_final with selected features only
    idx_map = {name:i for i,name in enumerate(feature_raw_names)}
    X_final = []
    for i in range(len(X_scaled)):
        row = [ X_scaled[i][ idx_map[f] ] for f in selected ]
        X_final.append(row)

    # Train logistic regression
    print("Training logistic regression on selected features...")
    weights, bias = train_logreg(X_final, y, lr=0.05, epochs=5000, l2=1e-3, early_stop_rounds=300)

    # Evaluate on training data (simple)
    probs = [ sigmoid(bias + sum(weights[j]*X_final[i][j] for j in range(len(weights)))) for i in range(len(X_final)) ]
    preds = [1 if p>=0.5 else 0 for p in probs]
    tp=fp=tn=fn=0
    for i in range(len(y)):
        if y[i]==1 and preds[i]==1: tp+=1
        if y[i]==0 and preds[i]==1: fp+=1
        if y[i]==0 and preds[i]==0: tn+=1
        if y[i]==1 and preds[i]==0: fn+=1
    acc = (tp+tn)/max(1, tp+tn+fp+fn)
    prec = tp/(tp+fp) if (tp+fp)>0 else 0.0
    rec = tp/(tp+fn) if (tp+fn)>0 else 0.0
    f1 = 2*prec*rec/(prec+rec) if (prec+rec)>0 else 0.0
    print(f"Train acc={acc:.4f} prec={prec:.4f} rec={rec:.4f} f1={f1:.4f}")

    # Save model artifact with all info needed for inference (preprocessing metadata + selected features + scaling + weights)
    model = {
        "preprocessing": {
            "base_features": pp['base_features'],
            "derived_features": pp['derived_features'],
            "medians": pp['medians'],
            "means": pp['means'],
            "stds": pp['stds'],
            "iqr": pp['iqr'],
            "log_fields": pp['log_fields']
        },
        "candidate_feature_list": feature_raw_names,
        "selected_features": selected,
        "scaling": {
            "col_means": {name: col_means[idx_map[name]] for name in feature_raw_names},
            "col_stds": {name: col_stds[idx_map[name]] for name in feature_raw_names}
        },
        "weights": { selected[i]: weights[i] for i in range(len(selected)) },
        "bias": bias,
        "metadata": {
            "train_samples": len(X_final),
            "top_k": TOP_K
        }
    }
    Path(OUT_MODEL).write_text(json.dumps(model, indent=2))
    print(f"Wrote model to {OUT_MODEL}")

if __name__ == "__main__":
    # small helper forward-declare to satisfy derived lambda references
    def to_float(x): return None if x is None or x=='' else float(x) if str(x).replace('.','',1).lstrip('-').isdigit() else None
    # run main
    main()
