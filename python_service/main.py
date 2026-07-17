from fastapi import FastAPI

from modeling import (
    build_narrative,
    compute_component_scores,
    compute_data_completeness,
    compute_gap_score,
    generate_policy,
    get_risk_level,
)
from schemas import AnalysisResult, InstitutionData


app = FastAPI(
    title="ICT Gap Analysis API",
    version="1.0.0",
    description=(
        "Calculates ICT infrastructure gaps for public institutions "
        "using infrastructure, connectivity, and obsolescence indicators."
    ),
)


@app.get("/")
def health_check():
    return {
        "service": "ICT Gap Analysis API",
        "status": "running",
    }


@app.post("/analyze", response_model=AnalysisResult)
def analyze_endpoint(data: InstitutionData) -> AnalysisResult:
    completeness = compute_data_completeness(data)
    components = compute_component_scores(data)
    score = compute_gap_score(data)
    risk = get_risk_level(score)

    return AnalysisResult(
        institution_id=data.id,
        gap_score=score,
        risk_level=risk,
        infrastructure_deficit_score=components[
            "infrastructure_deficit_score"
        ],
        connectivity_divide_score=components[
            "connectivity_divide_score"
        ],
        technological_obsolescence_score=components[
            "technological_obsolescence_score"
        ],
        data_completeness_score=completeness,
        infrastructure_narrative=build_narrative(data),
        policy_recommendation=generate_policy(data, score),
    )