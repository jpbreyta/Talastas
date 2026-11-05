"""Dependency-free formula parity checks. Schema/API validation needs Pydantic separately."""
import ast
import importlib.util
import json
from pathlib import Path
import subprocess
import sys
import types
from types import SimpleNamespace
root = Path(__file__).resolve().parents[1]
for path in (root / 'python_service').glob('*.py'):
    ast.parse(path.read_text())
sys.modules['schemas'] = SimpleNamespace(InstitutionData=SimpleNamespace)
spec = importlib.util.spec_from_file_location('modeling', root / 'python_service/modeling.py')
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)
base = dict(id=1, total_applicable_inventory_count=10, active_applicable_inventory_count=8,
            retired_applicable_inventory_count=2, functional_applicable_inventory_count=7,
            defective_applicable_inventory_count=1, has_internet_connection=True,
            bandwidth_allocation_mbps=100, download_speed_mbps=40,
            internet_connections=None, reported_internet_connection_count=None, inventory_invalid_inputs={})
changes = [{}, {'retired_applicable_inventory_count': None}, {'retired_applicable_inventory_count': 0},
           {'active_applicable_inventory_count': 0}, {'defective_applicable_inventory_count': 9},
           {'download_speed_mbps': None}, {'has_internet_connection': False},
           {'total_applicable_inventory_count': 0, 'active_applicable_inventory_count': 0,
            'retired_applicable_inventory_count': 0, 'functional_applicable_inventory_count': 0,
            'defective_applicable_inventory_count': 0},
           {'total_applicable_inventory_count': None}, {'inventory_invalid_inputs': {'row': 'Invalid subset'}}]
for change in changes:
    payload = dict(base, **change)
    inst = SimpleNamespace(**payload)
    php_payload = {key: value for key, value in payload.items() if key != 'internet_connections'}
    php = json.loads(subprocess.check_output(['php', str(root / 'tools/verify-analytics-calculations.php'),
                                             json.dumps(php_payload)], text=True))
    actual = dict(m.compute_component_scores(inst), gap_score=m.compute_gap_score(inst))
    for key, value in actual.items():
        assert php[key] == value, (change, key, php[key], value)
print(f'{len(changes)} Laravel/Python formula parity cases passed (schema stub; no database or network).')
