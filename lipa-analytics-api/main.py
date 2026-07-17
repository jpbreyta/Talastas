# main.py
from fastapi import FastAPI
from schemas import InstitutionData, AnalysisResult
from modeling import compute_gap_score, build_narrative, generate_policy

app = FastAPI(title="Gap Analysis API")

@app.post("/analyze", response_model=AnalysisResult)
def analyze_endpoint(data: InstitutionData):
    # 1. Run the Model
    score = compute_gap_score(data)
    
    # 2. Evaluate the Risk Level
    risk = "critical" if score >= 80 else "warning" if score >= 50 else "adequate"
    
    # 3. Return Deployment Package
    return AnalysisResult(
        institution_id=data.id,
        gap_score=score,
        risk_level=risk,
        infrastructure_narrative=build_narrative(data),
        policy_recommendation=generate_policy(data, score)
    )