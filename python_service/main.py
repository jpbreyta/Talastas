from fastapi import FastAPI
from modeling import CONNECTIVITY_METHOD, calculate
from schemas import AnalysisResult, InstitutionData

app = FastAPI(title='TALASTAS ICT Gap Analysis API', version='4.2.0')


@app.get('/')
def health_check():
    return {'service': 'TALASTAS ICT Gap Analysis API', 'version': '4.2.0',
            'status': 'running', 'method': 'explicit-input-deterministic'}


@app.post('/analyze', response_model=AnalysisResult)
def analyze_endpoint(data: InstitutionData) -> AnalysisResult:
    result = calculate(data)
    return AnalysisResult(institution_id=data.id, connectivity_method=CONNECTIVITY_METHOD, **result)
