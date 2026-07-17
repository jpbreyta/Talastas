# schemas.py
from pydantic import BaseModel
from typing import Optional

class InstitutionData(BaseModel):
    id: int
    name: str
    internet_speed: Optional[float] = 0.0
    student_population: Optional[int] = 0
    needs_maintenance: bool = False
    has_computer_lab: bool = False
    has_smart_tv: bool = False
    has_public_address_system: bool = False
    active_computers_count: int = 0
    smart_tvs_count: int = 0
    projectors_count: int = 0
    pa_systems_count: int = 0

class AnalysisResult(BaseModel):
    institution_id: int
    gap_score: float
    risk_level: str
    infrastructure_narrative: str
    policy_recommendation: str