from typing import Optional

from schemas import InstitutionData


INFRASTRUCTURE_WEIGHT = 0.40
CONNECTIVITY_WEIGHT = 0.35
OBSOLESCENCE_WEIGHT = 0.25

MINIMUM_COMPLETENESS_FOR_SCORING = 60.0


def clamp(value: float, minimum: float = 0.0, maximum: float = 100.0) -> float:
    return max(minimum, min(maximum, value))


def weighted_average(items: list[tuple[Optional[float], float]]) -> Optional[float]:
    available_items = [
        (value, weight)
        for value, weight in items
        if value is not None
    ]

    if not available_items:
        return None

    total_weight = sum(weight for _, weight in available_items)

    if total_weight <= 0:
        return None

    result = sum(value * weight for value, weight in available_items) / total_weight

    return round(clamp(result), 2)


def compute_data_completeness(inst: InstitutionData) -> float:
    required_fields = [
        inst.institution_type,
        inst.internet_speed,
        inst.active_computers_count,
        inst.total_inventory_count,
    ]

    if inst.institution_type == "School":
        required_fields.append(inst.student_population)
    else:
        required_fields.append(inst.staff_population)

    supporting_fields = [
        inst.has_computer_lab,
        inst.has_smart_tv,
        inst.has_projected_whiteboard,
        inst.has_public_address_system,
        inst.smart_tvs_count,
        inst.projectors_count,
        inst.pa_systems_count,
        inst.old_equipment_count,
        inst.poor_condition_count,
        inst.needs_repair_count,
        inst.retired_equipment_count,
        inst.average_equipment_age,
    ]

    required_complete = sum(value is not None for value in required_fields)
    supporting_complete = sum(value is not None for value in supporting_fields)

    required_score = (
        required_complete / len(required_fields)
        if required_fields
        else 0
    )

    supporting_score = (
        supporting_complete / len(supporting_fields)
        if supporting_fields
        else 0
    )

    completeness = (required_score * 70) + (supporting_score * 30)

    return round(clamp(completeness), 2)


def has_sufficient_data(inst: InstitutionData) -> bool:
    if compute_data_completeness(inst) < MINIMUM_COMPLETENESS_FOR_SCORING:
        return False

    if inst.internet_speed is None:
        return False

    if inst.active_computers_count is None:
        return False

    if inst.total_inventory_count is None:
        return False

    if inst.institution_type == "School":
        return inst.student_population is not None

    return inst.staff_population is not None


def compute_asset_to_user_deficit(inst: InstitutionData) -> Optional[float]:
    active_computers = inst.active_computers_count

    if active_computers is None:
        return None

    user_population = inst.user_population()

    if user_population <= 0:
        return None

    if active_computers == 0:
        return 100.0

    ratio = user_population / active_computers

    if inst.institution_type == "School":
        if ratio > 40:
            return 100.0
        if ratio > 25:
            return 75.0
        if ratio > 15:
            return 50.0
        if ratio > 10:
            return 25.0
        return 0.0

    if ratio > 5:
        return 100.0
    if ratio > 3:
        return 75.0
    if ratio > 2:
        return 50.0
    if ratio > 1:
        return 25.0

    return 0.0


def compute_infrastructure_deficit(inst: InstitutionData) -> Optional[float]:
    asset_ratio_deficit = compute_asset_to_user_deficit(inst)

    smart_display_available = None
    if inst.smart_tvs_count is not None or inst.projectors_count is not None:
        smart_tv_count = inst.smart_tvs_count or 0
        projector_count = inst.projectors_count or 0
        smart_display_available = 0.0 if (smart_tv_count + projector_count) > 0 else 100.0
    elif inst.has_smart_tv is not None or inst.has_projected_whiteboard is not None:
        has_display = bool(inst.has_smart_tv or inst.has_projected_whiteboard)
        smart_display_available = 0.0 if has_display else 100.0

    pa_system_deficit = None
    if inst.pa_systems_count is not None:
        pa_system_deficit = 0.0 if inst.pa_systems_count > 0 else 100.0
    elif inst.has_public_address_system is not None:
        pa_system_deficit = 0.0 if inst.has_public_address_system else 100.0

    if inst.institution_type == "School":
        computer_lab_deficit = None

        if inst.has_computer_lab is not None:
            computer_lab_deficit = 0.0 if inst.has_computer_lab else 100.0

        return weighted_average([
            (asset_ratio_deficit, 60),
            (computer_lab_deficit, 15),
            (smart_display_available, 15),
            (pa_system_deficit, 10),
        ])

    return weighted_average([
        (asset_ratio_deficit, 70),
        (smart_display_available, 15),
        (pa_system_deficit, 15),
    ])


def compute_connectivity_divide(inst: InstitutionData) -> Optional[float]:
    speed = inst.internet_speed

    if speed is None:
        return None

    if speed == 0:
        return 100.0
    if speed < 10:
        return 90.0
    if speed < 25:
        return 70.0
    if speed < 50:
        return 45.0
    if speed < 100:
        return 20.0

    return 0.0


def equipment_ratio(
    count: Optional[int],
    total: Optional[int],
) -> Optional[float]:
    if count is None or total is None:
        return None

    if total <= 0:
        return 0.0

    return clamp((count / total) * 100)


def compute_technological_obsolescence(inst: InstitutionData) -> Optional[float]:
    total = inst.total_inventory_count

    old_ratio = equipment_ratio(inst.old_equipment_count, total)
    poor_ratio = equipment_ratio(inst.poor_condition_count, total)
    repair_ratio = equipment_ratio(inst.needs_repair_count, total)
    retired_ratio = equipment_ratio(inst.retired_equipment_count, total)

    condition_deficit = None
    if poor_ratio is not None or repair_ratio is not None:
        condition_deficit = clamp(
            (poor_ratio or 0) + (repair_ratio or 0)
        )

    age_deficit = None
    if inst.average_equipment_age is not None:
        age = inst.average_equipment_age

        if age >= 8:
            age_deficit = 100.0
        elif age >= 6:
            age_deficit = 75.0
        elif age >= 4:
            age_deficit = 50.0
        elif age >= 2:
            age_deficit = 20.0
        else:
            age_deficit = 0.0

    maintenance_deficit = 100.0 if inst.needs_maintenance else 0.0

    return weighted_average([
        (old_ratio, 30),
        (condition_deficit, 35),
        (retired_ratio, 15),
        (age_deficit, 15),
        (maintenance_deficit, 5),
    ])


def compute_component_scores(inst: InstitutionData) -> dict[str, Optional[float]]:
    return {
        "infrastructure_deficit_score": compute_infrastructure_deficit(inst),
        "connectivity_divide_score": compute_connectivity_divide(inst),
        "technological_obsolescence_score": compute_technological_obsolescence(inst),
    }


def compute_gap_score(inst: InstitutionData) -> Optional[float]:
    if not has_sufficient_data(inst):
        return None

    components = compute_component_scores(inst)

    weighted_components = [
        (
            components["infrastructure_deficit_score"],
            INFRASTRUCTURE_WEIGHT,
        ),
        (
            components["connectivity_divide_score"],
            CONNECTIVITY_WEIGHT,
        ),
        (
            components["technological_obsolescence_score"],
            OBSOLESCENCE_WEIGHT,
        ),
    ]

    score = weighted_average(weighted_components)

    if score is None:
        return None

    return round(clamp(score), 2)


def get_risk_level(score: Optional[float]) -> str:
    if score is None:
        return "insufficient_data"

    if score >= 80:
        return "critical"

    if score >= 50:
        return "warning"

    return "adequate"


def build_narrative(inst: InstitutionData) -> str:
    completeness = compute_data_completeness(inst)

    if not has_sufficient_data(inst):
        return (
            f"The available information for {inst.name} is only "
            f"{completeness:.0f}% complete. The institution must update its "
            "population, connectivity, computer, and inventory records before "
            "a reliable ICT gap assessment can be produced."
        )

    components = compute_component_scores(inst)
    score = compute_gap_score(inst)

    infrastructure = components["infrastructure_deficit_score"] or 0
    connectivity = components["connectivity_divide_score"] or 0
    obsolescence = components["technological_obsolescence_score"] or 0

    highest_name, highest_score = max(
        [
            ("infrastructure availability", infrastructure),
            ("internet connectivity", connectivity),
            ("equipment condition and age", obsolescence),
        ],
        key=lambda item: item[1],
    )

    return (
        f"{inst.name} has an overall ICT gap score of {score:.1f} out of 100. "
        f"The most significant concern is {highest_name}, with a deficiency "
        f"score of {highest_score:.1f}. The result is based on the institution's "
        "asset-to-user ratio, internet speed, available ICT facilities, and the "
        "condition and age of recorded equipment."
    )


def generate_policy(inst: InstitutionData, score: Optional[float]) -> str:
    if score is None:
        return (
            "Complete and verify the institution's required records before "
            "making procurement or resource-allocation decisions."
        )

    components = compute_component_scores(inst)
    recommendations: list[str] = []

    infrastructure = components["infrastructure_deficit_score"]
    connectivity = components["connectivity_divide_score"]
    obsolescence = components["technological_obsolescence_score"]

    if infrastructure is not None and infrastructure >= 50:
        recommendations.append(
            "Prioritize additional usable computers and essential ICT facilities "
            "based on the number of students or staff served."
        )

    if connectivity is not None and connectivity >= 50:
        recommendations.append(
            "Review the institution's internet plan and prioritize a faster or "
            "more reliable connection."
        )

    if obsolescence is not None and obsolescence >= 50:
        recommendations.append(
            "Schedule replacement or repair of old, poor-condition, and "
            "non-operational equipment."
        )

    if inst.needs_maintenance:
        recommendations.append(
            "Conduct an on-site maintenance assessment and document the affected equipment."
        )

    if not recommendations:
        recommendations.append(
            "Maintain the current ICT resources and continue periodic inventory "
            "and connectivity monitoring."
        )

    if score >= 80:
        priority = "Treat this institution as an urgent ICT investment priority."
    elif score >= 50:
        priority = "Include this institution in the next ICT improvement plan."
    else:
        priority = "Continue routine monitoring and preventive maintenance."

    return priority + " " + " ".join(recommendations)