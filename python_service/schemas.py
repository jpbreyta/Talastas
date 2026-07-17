from typing import Literal, Optional

from pydantic import BaseModel, Field, model_validator


InstitutionType = Literal[
    "School",
    "Barangay Hall",
    "Local Government Office",
]

RiskLevel = Literal[
    "adequate",
    "warning",
    "critical",
    "insufficient_data",
]


class InstitutionData(BaseModel):
    id: int
    name: str
    institution_type: InstitutionType

    internet_speed: Optional[float] = Field(default=None, ge=0)

    student_population: Optional[int] = Field(default=None, ge=0)
    staff_population: Optional[int] = Field(default=None, ge=0)

    needs_maintenance: bool = False

    has_computer_lab: Optional[bool] = None
    has_smart_tv: Optional[bool] = None
    has_projected_whiteboard: Optional[bool] = None
    has_public_address_system: Optional[bool] = None

    active_computers_count: Optional[int] = Field(default=None, ge=0)
    smart_tvs_count: Optional[int] = Field(default=None, ge=0)
    projectors_count: Optional[int] = Field(default=None, ge=0)
    pa_systems_count: Optional[int] = Field(default=None, ge=0)

    total_inventory_count: Optional[int] = Field(default=None, ge=0)
    old_equipment_count: Optional[int] = Field(default=None, ge=0)
    poor_condition_count: Optional[int] = Field(default=None, ge=0)
    needs_repair_count: Optional[int] = Field(default=None, ge=0)
    retired_equipment_count: Optional[int] = Field(default=None, ge=0)
    average_equipment_age: Optional[float] = Field(default=None, ge=0)

    @model_validator(mode="after")
    def validate_population(self):
        if self.institution_type == "School":
            if self.student_population is None:
                raise ValueError(
                    "student_population is required for School institutions."
                )
        else:
            if self.staff_population is None:
                raise ValueError(
                    "staff_population is required for non-school institutions."
                )

        return self

    def user_population(self) -> int:
        if self.institution_type == "School":
            return self.student_population or 0

        return self.staff_population or 0


class AnalysisResult(BaseModel):
    institution_id: int
    gap_score: Optional[float]
    risk_level: RiskLevel

    infrastructure_deficit_score: Optional[float] = None
    connectivity_divide_score: Optional[float] = None
    technological_obsolescence_score: Optional[float] = None

    data_completeness_score: float = Field(ge=0, le=100)

    infrastructure_narrative: str
    policy_recommendation: str