from typing import Any, Literal, Optional
from pydantic import BaseModel, Field, field_validator


class InternetConnectionData(BaseModel):
    # Value eligibility belongs to the indicator, not whole-request validation.
    connection_number: Any = None
    plan_classification: Any = 'not_recorded'
    plan_reason: Any = None
    advertised_download_mbps: Any = None
    download_speed_mbps: Any = None


class InstitutionData(BaseModel):
    id: int = Field(gt=0)
    has_internet_connection: Any = None
    bandwidth_allocation_mbps: Any = None
    download_speed_mbps: Any = None
    internet_connections: Optional[list[dict[str, Any]]] = None
    reported_internet_connection_count: Any = None
    inventory: Optional[list[dict[str, Any]]] = None
    inventory_invalid_inputs: dict[str, str] = Field(default_factory=dict)
    inventory_coverage: Any = 'saved_applicable_rows'
    missing_expected_inventory: list[str] = Field(default_factory=list)
    total_applicable_inventory_count: Any = None
    active_applicable_inventory_count: Any = None
    retired_applicable_inventory_count: Any = None
    functional_applicable_inventory_count: Any = None
    defective_applicable_inventory_count: Any = None
    defective_active_applicable_inventory_count: Any = None

    @field_validator('inventory_invalid_inputs', mode='before')
    @classmethod
    def normalize_empty_metadata(cls, value):
        return {} if value == [] else value


class AnalysisResult(BaseModel):
    institution_id: int
    gap_score: Optional[float] = None
    risk_level: Literal['assessed', 'insufficient_data']
    infrastructure_deficit_score: Optional[float] = None
    connectivity_divide_score: Optional[float] = None
    technological_obsolescence_score: Optional[float] = None
    connectivity_method: str
    processing_status: str = 'completed'
    assessment_status: str
    missing_inputs: list[str]
    invalid_inputs: dict[str, str]
    unavailable_reasons: dict[str, str]
    inventory_inputs: dict[str, Any]
