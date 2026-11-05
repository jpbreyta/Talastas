"""Deterministic parity contract with Laravel's IctGapBaselineCalculator.

Normal jobs send prepared connection classifications. Raw connection responses
use the same conservative unit-bearing plan parser in both runtimes.
"""
from decimal import Decimal, ROUND_HALF_UP
import math
import re

CONNECTIVITY_METHOD = 'advertised-speed-shortfall-v1'
FIELDS = ['total', 'active', 'retired', 'functional', 'defective', 'defective_active']


def round_score(value):
    return float(Decimal(str(value)).quantize(Decimal('0.01'), rounding=ROUND_HALF_UP))


def quantity(value):
    if type(value) is int and 0 <= value <= 9223372036854775807:
        return value
    if isinstance(value, str) and re.fullmatch(r'[0-9]+', value) and int(value) <= 9223372036854775807:
        return int(value)
    return None


def valid_speed(value, positive=False):
    if value is None or isinstance(value, bool):
        return False
    try:
        number = float(value)
        return math.isfinite(number) and (number > 0 if positive else number >= 0) and number <= 1000000
    except (ValueError, TypeError, OverflowError):
        return False



def prepare_connection(row):
    """Same conservative parser as PHP; never convert a price or bare number."""
    plan = str(row.get('plan') or '').strip()
    parsed = dict(plan_classification='not_recorded', advertised_download_mbps=None, plan_reason='plan_not_recorded')
    if plan:
        matches = list(re.finditer(r'(?<![^\W_])(?<![.,])((?:\d{1,3}(?:,\d{3})+|\d+)(?:\.\d+)?)\s*(kbps|mbps|gbps|kbit/s|mbit/s|gbit/s)\b', plan, re.I))
        parsed = dict(plan_classification='ambiguous', advertised_download_mbps=None, plan_reason='plan_ambiguous')
        if len(matches) == 1:
            match = matches[0]
            prefix = plan[:match.start()]
            ambiguous_range = re.search(r'\d\s*(?:(?:[kmg]bps|[kmg]bit/s)\s*)?(?:[-–—/]|to\b)\s*\d|\bor\s+\d', plan, re.I)
            negative = re.search(r'[-−]\s*$', prefix)
            price_speed = re.search(r'(?:₱|\$|\bPHP|\bUSD)\s*$', prefix, re.I)
            upload_only = re.search(r'\b(upload|upstream|up(?!\s+to\b))\b', plan, re.I)
            unit = match[2]
            if not (ambiguous_range or negative or price_speed or upload_only or unit in ['KBps', 'MBps', 'GBps']):
                speed = float(match[1].replace(',', '')) * {'g': 1000, 'k': .001, 'm': 1}[unit[0].lower()]
                if math.isfinite(speed) and speed > 0:
                    parsed = dict(plan_classification='identifiable', advertised_download_mbps=speed, plan_reason=None)
                else:
                    parsed['plan_reason'] = 'invalid_advertised_speed'
        if not matches and re.search(r'\d', plan) and re.search(r'₱|\bPHP(?=\b|\d)|\bUSD(?=\b|\d)|\$|\bpesos?\b', plan, re.I):
            parsed.update(plan_classification='price_only', plan_reason='plan_price_only')
    result = dict(connection_number=row.get('connection_number', '?'), **parsed,
                  advertised_speed_source='plan' if parsed['plan_classification'] == 'identifiable' else None)
    explicit = row.get('advertised_download_mbps')
    if explicit is not None and explicit != '':
        if not valid_speed(explicit, True):
            result.update(plan_classification='ambiguous', plan_reason='invalid_advertised_speed',
                          advertised_download_mbps=None, advertised_speed_source='recorded')
        elif parsed['plan_classification'] == 'identifiable' and abs(float(explicit) - parsed['advertised_download_mbps']) > .000001:
            result.update(plan_classification='ambiguous', plan_reason='advertised_speed_conflict',
                          advertised_download_mbps=None, advertised_speed_source='conflict')
        else:
            result.update(plan_classification='identifiable', plan_reason=None,
                          advertised_download_mbps=float(explicit), advertised_speed_source='recorded')
    for field in ['download_speed_mbps', 'upload_speed_mbps', 'latency_ms', 'throughput_mbps']:
        value = row.get(field)
        result[field] = None if value == '' else value
    return result

def count_errors(row):
    total = quantity(row.get('total_count'))
    errors = {'total_count': 'Enter Total for a recorded inventory item. Availability fields may be left blank.'} if total is None else {}
    for field in FIELDS:
        key = field + '_count'
        raw = row.get(key)
        if raw is not None and raw != '' and quantity(raw) is None:
            errors[key] = 'Must be a nonnegative integer.'
    for field in FIELDS[1:]:
        key = field + '_count'
        value = quantity(row.get(key))
        if total is not None and value is not None and value > total:
            label = 'Defective within Active' if field == 'defective_active' else field.capitalize()
            errors[key] = label + ' units cannot exceed Total units.'
    within_active = quantity(row.get('defective_active_count'))
    for field in ['active_count', 'defective_count', 'total_count']:
        raw = row.get(field)
        if within_active is not None and raw is not None and raw != '' and quantity(raw) is None:
            errors['defective_active_count'] = 'Recorded population counts must be valid to validate Defective within Active.'
    for key, label in [('active_count', 'Active'), ('defective_count', 'general Defective')]:
        population = quantity(row.get(key))
        if within_active is not None and population is not None and within_active > population:
            errors['defective_active_count'] = 'Defective within Active cannot exceed ' + label + '.'
    return errors


def inventory_reason(rows, errors, required, data):
    if data.get('missing_expected_inventory'):
        return 'Record the expected applicable inventory: ' + ', '.join(data['missing_expected_inventory']) + '.'
    if not rows:
        return 'Record inventory in the selected calculation scope.'
    labels = {'active_count': 'Record Active inventory counts.',
              'defective_active_count': 'Record defective units within Active inventory.',
              'total_count': 'Record Total inventory counts.', 'retired_count': 'Record Retired inventory counts.'}
    for index, row in enumerate(rows):
        for field in required:
            if field in errors[index]:
                return errors[index][field]
            if row.get(field) is None or row.get(field) == '':
                return labels[field]
    return None


def connectivity(data):
    rows = data.get('internet_connections')
    status = data.get('has_internet_connection')
    reported = data.get('reported_internet_connection_count')
    if rows is None:
        if 'reported_internet_connection_count' not in data:
            reported = 0 if status is False else 1 if status is True else None
        rows = [dict(connection_number=1, plan_classification='identifiable',
                     advertised_download_mbps=data.get('bandwidth_allocation_mbps'),
                     download_speed_mbps=data.get('download_speed_mbps'))] if status is True else []
    errors = {}
    if status is not None and type(status) is not bool:
        errors['has_internet_connection'] = 'Select Yes, No, or Not recorded for Internet service.'
    valid_reported = quantity(reported)
    if reported is not None and (valid_reported is None or valid_reported > 255):
        errors['reported_internet_connection_count'] = 'Reported connection count must be a whole number from 0 to 255.'
    if ((status is False and (rows or (valid_reported is not None and valid_reported > 0)))
            or (status is True and valid_reported == 0)):
        errors['has_internet_connection'] = 'Internet service status conflicts with the reported count or saved connections.'
    if valid_reported is not None and valid_reported < len(rows):
        errors['reported_internet_connection_count'] = 'Reported connection count cannot be less than the number of saved connections.'
    numbers = [quantity(row.get('connection_number')) for row in rows]
    if len(set(numbers)) != len(rows) or any(n is None or n < 1 or n > 255 for n in numbers):
        errors['internet_connections'] = 'Connection numbers must be unique whole numbers from 1 to 255.'
    messages = list(errors.values())
    if not errors and status is False and valid_reported == 0 and not rows:
        return 100.0, messages, errors
    if status is None:
        messages.append('Record Internet service status.')
    if reported is None:
        messages.append('Record the number of Internet connections.')
    elif valid_reported is not None and valid_reported > len(rows):
        messages.append('Some reported connections have no recorded details.')
    scores = []
    for row in rows:
        if 'plan' in row:
            row = prepare_connection(row)
        number = row.get('connection_number', '?')
        advertised = row.get('advertised_download_mbps')
        download = row.get('download_speed_mbps')
        bad = False
        if row.get('plan_reason') == 'advertised_speed_conflict':
            message = f'Recorded advertised download conflicts with the plan speed for connection {number}.'
            messages.append(message)
            errors[f'internet_connections.{number}.advertised_download_mbps'] = message
            bad = True
        elif row.get('plan_classification') != 'identifiable' or not valid_speed(advertised, True):
            messages.append(f'Enter advertised download speed in Mbps for connection {number}.')
            if (advertised is not None and advertised != '' and not valid_speed(advertised, True)) or row.get('plan_reason') == 'invalid_advertised_speed':
                errors[f'internet_connections.{number}.advertised_download_mbps'] = 'Advertised download must be a positive finite value in Mbps.'
            bad = True
        if not valid_speed(download):
            messages.append(f'Record a valid measured download speed for connection {number}.')
            if download is not None and download != '':
                errors[f'internet_connections.{number}.download_speed_mbps'] = 'Measured download must be a nonnegative finite value in Mbps.'
            bad = True
        if not bad:
            scores.append(round_score(max(float(advertised) - float(download), 0) / float(advertised) * 100))
    score = round_score(sum(scores) / len(rows)) if rows and status is True and not messages and len(scores) == valid_reported else None
    return score, messages, errors


def calculate(data):
    if not isinstance(data, dict):
        data = data.model_dump(exclude_unset=True) if hasattr(data, 'model_dump') else vars(data)
    rows = data.get('inventory')
    if rows is None:
        rows = [{field + '_count': data.get(field + '_applicable_inventory_count') for field in FIELDS}]
    rows = [dict(row, functional_count=None) if row.get('functional_count_recorded') is False else row for row in rows]
    errors = [count_errors(row) for row in rows]
    invalid = dict(data.get('inventory_invalid_inputs') or {})
    missing = []
    for index, row in enumerate(rows):
        missing.extend(f'inventory.{index}.{field}_count' for field in FIELDS
                       if row.get(field + '_count') is None or row.get(field + '_count') == '')
        invalid.update({f'inventory.{index}.{key}': value for key, value in errors[index].items()})
    totals = {}
    recorded = {}
    for field in FIELDS:
        key = field + '_count'
        values = [quantity(row.get(key)) for row in rows]
        recorded[key] = sum(value is not None for value in values)
        totals[key] = sum(values) if rows and recorded[key] == len(rows) else None
    infra_reason = inventory_reason(rows, errors, ['active_count', 'defective_active_count'], data)
    retired_reason = inventory_reason(rows, errors, ['total_count', 'retired_count'], data)
    if infra_reason is None and totals['active_count'] == 0:
        infra_reason = 'Active count must be greater than zero.'
    if retired_reason is None and totals['total_count'] == 0:
        retired_reason = 'Total count must be greater than zero.'
    infra = round_score(totals['defective_active_count'] / totals['active_count'] * 100) if infra_reason is None else None
    retired = round_score(totals['retired_count'] / totals['total_count'] * 100) if retired_reason is None else None
    connection_score, messages, connection_errors = connectivity(data)
    invalid.update(connection_errors)
    connection_reason = messages[0] if connection_score is None and messages else None
    if connection_reason:
        missing.append('complete_internet_connection_inputs')
    values = [infra, connection_score, retired]
    overall = round_score(sum(values) / 3) if all(value is not None for value in values) else None
    status = 'assessed' if overall is not None else 'insufficient_data'
    return dict(gap_score=overall, risk_level=status, infrastructure_deficit_score=infra,
                connectivity_divide_score=connection_score, technological_obsolescence_score=retired,
                processing_status='completed', assessment_status=status, missing_inputs=missing, invalid_inputs=invalid,
                unavailable_reasons={key: value for key, value in [
                    ('infrastructure_deficit_score', infra_reason), ('connectivity_divide_score', connection_reason),
                    ('technological_obsolescence_score', retired_reason)] if value is not None},
                inventory_inputs=dict(totals=totals, recorded_row_counts=recorded, included_rows=len(rows),
                                      coverage=data.get('inventory_coverage', 'saved_applicable_rows'),
                                      missing_expected_inventory=data.get('missing_expected_inventory') or []))


def compute_component_scores(inst):
    result = calculate(inst)
    return {key: result[key] for key in ['infrastructure_deficit_score', 'connectivity_divide_score', 'technological_obsolescence_score']}


def compute_infrastructure_deficit(inst):
    return calculate(inst)['infrastructure_deficit_score']


def compute_connectivity_divide(inst):
    return calculate(inst)['connectivity_divide_score']


def compute_technological_obsolescence(inst):
    return calculate(inst)['technological_obsolescence_score']


def compute_gap_score(inst):
    return calculate(inst)['gap_score']


def has_sufficient_data(inst):
    return compute_gap_score(inst) is not None


def get_assessment_status(score):
    return 'assessed' if score is not None else 'insufficient_data'
