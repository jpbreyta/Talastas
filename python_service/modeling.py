# modeling.py
from schemas import InstitutionData

def compute_gap_score(inst: InstitutionData) -> float:
    score = 0.0
    
    # 1. Internet Speed
    if not inst.internet_speed or inst.internet_speed == 0:
        score += 30
    elif inst.internet_speed < 10:
        score += 25
    elif inst.internet_speed < 25:
        score += 20
    elif inst.internet_speed < 50:
        score += 10

    # 2. Computer Lab
    if inst.active_computers_count == 0:
        score += 30
    elif inst.active_computers_count < 10:
        score += 25
    elif inst.active_computers_count < 20:
        score += 15
    elif inst.active_computers_count < 30:
        score += 8

    # 3. Smart Display & Projectors
    if inst.smart_tvs_count == 0:
        score += 10
    if inst.projectors_count == 0:
        score += 5

    # 4. PA System
    if inst.pa_systems_count == 0:
        score += 10

    # 5. Student-Computer Ratio
    if inst.student_population and inst.active_computers_count > 0:
        ratio = inst.student_population / inst.active_computers_count
        if ratio > 40:
            score += 15
        elif ratio > 25:
            score += 10
        elif ratio > 15:
            score += 5
    else:
        score += 10

    # 6. Maintenance Status
    if inst.needs_maintenance:
        score += 5

    return min(100.0, score)

def build_narrative(inst: InstitutionData) -> str:
    # (Insert your PHP narrative logic here)
    return "Infrastructure narrative generated."

def generate_policy(inst: InstitutionData, score: float) -> str:
    # (Insert your PHP policy logic here)
    return "Policy recommendations generated."