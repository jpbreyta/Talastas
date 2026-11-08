"""Real Pydantic/API and PHP/Python parity checks. No database, network or AI."""
import json
from pathlib import Path
import subprocess
import sys
root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(root / 'python_service'))
from modeling import calculate
from schemas import InstitutionData
from main import analyze_endpoint

base = dict(id=1,total_applicable_inventory_count=100,active_applicable_inventory_count=80,
            retired_applicable_inventory_count=20,defective_active_applicable_inventory_count=10,
            defective_applicable_inventory_count=15,functional_applicable_inventory_count=60,
            has_internet_connection=True,reported_internet_connection_count=1,
            internet_connections=[dict(connection_number=1,plan_classification='identifiable',
                                       advertised_download_mbps=100,download_speed_mbps=60,upload_speed_mbps=60)])
cases = [base]
for field, values in {
 'active_applicable_inventory_count':[0,None,-1,1.5,'bad'],
 'defective_active_applicable_inventory_count':[0,None,-1,81,101,1.5,'bad'],
 'total_applicable_inventory_count':[0,None,-1,5,'bad'],
 'retired_applicable_inventory_count':[0,None,-1,101],
 'functional_applicable_inventory_count':[None,-1,101,'bad'],
 'defective_applicable_inventory_count':[None,9,-1,'bad'],
}.items():
    cases.extend(dict(base,**{field:value}) for value in values)
cases.extend([
 dict(base,inventory=[]),
 dict(base,missing_expected_inventory=['Confirmed applicable Desktop']),
 dict(base,has_internet_connection=False,reported_internet_connection_count=0,internet_connections=[]),
 dict(base,has_internet_connection=None,reported_internet_connection_count=None,internet_connections=[]),
 dict(base,has_internet_connection=False),
 dict(base,reported_internet_connection_count=0),
 dict(base,reported_internet_connection_count=2),
 dict(base,has_internet_connection=None),
 dict(base,reported_internet_connection_count=None),
 dict(base,reported_internet_connection_count=2,internet_connections=base['internet_connections']+[
      dict(connection_number=2,plan_classification='identifiable',advertised_download_mbps=50,download_speed_mbps=50,upload_speed_mbps=50)]),
 dict(base,reported_internet_connection_count=2,internet_connections=base['internet_connections']+[
      dict(connection_number=2,plan_classification='identifiable',advertised_download_mbps=50,download_speed_mbps=None,upload_speed_mbps=50)]),
])
for plan, explicit in [('2000',None),('PHP 2000',None),('100 Mbps',None),('50-100 Mbps',None),
                       ('100 Mbps',50),('2000',50),('100 Mbps',100),('100 Mbps',0)]:
    c=dict(base,raw_connections=[dict(connection_number=1,plan=plan,advertised_download_mbps=explicit,download_speed_mbps=0.54,upload_speed_mbps=0.54)])
    cases.append(c)
for value in [None,0,-1,'bad',1000001]:
    cases.append(dict(base,internet_connections=[dict(base['internet_connections'][0],download_speed_mbps=value)]))
cases.append(dict(base,inventory=[dict(total_count=100,active_count=80,retired_count=20,defective_active_count=10,defective_count=15,functional_count=-1),
                                dict(total_count=0,active_count=0,retired_count=None,defective_active_count=None)]))
for plan in ['100 Mbps', '2000', 'PHP 2000', '50-100 Mbps', '1 Gbps', '500 kbps', '100 MBps', 'Upload 100 Mbps', '100 Mbps or 200 Mbps']:
    cases.append(dict(base,internet_connections=[dict(connection_number=1,plan=plan,download_speed_mbps=60,upload_speed_mbps=60)]))
for reported in ['bad', -1, 256, 1.5]:
    cases.append(dict(base,reported_internet_connection_count=reported))
cases.append(dict(base,inventory=[dict(total_count=100,active_count=80,retired_count=20,
                                     defective_active_count=10,defective_count=15,
                                     functional_count=999,functional_count_recorded=False)]))
for status, reported in [(False, 2), (False, None), (True, 2)]:
    legacy = dict(base,has_internet_connection=status,reported_internet_connection_count=reported,
                  bandwidth_allocation_mbps=100,download_speed_mbps=60,upload_speed_mbps=60)
    legacy.pop('internet_connections')
    cases.append(legacy)
for index, payload in enumerate(cases):
    response=json.loads(subprocess.check_output(['php',str(root / 'tools/verify-explicit-analytics.php'),json.dumps(payload)],text=True))
    data=InstitutionData.model_validate(response['payload'])
    python_result=calculate(data)
    php_result=response['result']
    # PHP serializes empty associative arrays as []; normalize maps for comparison.
    for key in ['invalid_inputs','unavailable_reasons']:
        php_result[key]=php_result[key] or {}
    assert php_result == python_result, (index,payload,php_result,python_result)
    api=analyze_endpoint(data).model_dump()
    for key,value in python_result.items():
        assert api[key] == value,(index,key)
print(f'{len(cases)} full-result parity cases and Pydantic/API responses passed.')

# Exercise the ASGI request/response boundary without sockets or httpx.
import asyncio
from main import app

async def request_json(payload):
    body = json.dumps(payload).encode()
    messages = []
    delivered = False
    async def receive():
        nonlocal delivered
        if not delivered:
            delivered = True
            return {'type': 'http.request', 'body': body, 'more_body': False}
        return {'type': 'http.disconnect'}
    async def send(message):
        messages.append(message)
    await app({'type': 'http', 'asgi': {'version': '3.0'}, 'http_version': '1.1',
               'method': 'POST', 'scheme': 'http', 'path': '/analyze', 'raw_path': b'/analyze',
               'root_path': '', 'query_string': b'', 'headers': [(b'content-type', b'application/json')],
               'server': ('fixture', 80), 'client': ('fixture', 1)}, receive, send)
    status = next(message['status'] for message in messages if message['type'] == 'http.response.start')
    result = json.loads(b''.join(message.get('body', b'') for message in messages if message['type'] == 'http.response.body'))
    return status, result

async def verify_asgi():
    status, result = await request_json(base)
    assert status == 200 and result['gap_score'] == 24.17
    status, result = await request_json(dict(base, functional_applicable_inventory_count=-1))
    assert status == 200 and result['gap_score'] == 24.17 and result['invalid_inputs']
    status, result = await request_json(dict(base, defective_active_applicable_inventory_count=None))
    assert status == 200 and result['gap_score'] is None and result['connectivity_divide_score'] == 40
    status, result = await request_json({'id': 0})
    assert status == 422
asyncio.run(verify_asgi())
print('4 ASGI HTTP-boundary checks passed without network access.')
