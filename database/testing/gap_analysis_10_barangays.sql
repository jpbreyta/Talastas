-- Synthetic TALASTAS test data. Requires migrations through 2026-10-27 and
-- BarangaySeeder + InfrastructureComponentSeeder. Run this entire script.
-- Selects the first 10 mapped barangays alphabetically; no real survey claims.
-- Existing institutions are untouched. Reruns retain this dataset unchanged.
-- No AI calls, prescriptions, narratives, recommendations, or priorities.
BEGIN;

CREATE TEMP TABLE gap_test_profiles ON COMMIT DROP AS
SELECT b.id AS barangay_id, b.name AS barangay_name,
       row_number() OVER (ORDER BY b.name, b.id)::integer AS severity,
       ST_PointOnSurface(COALESCE(b.geom, ST_SetSRID(ST_GeomFromGeoJSON(b.geometry::text),4326))) AS point
FROM barangays b
WHERE b.geom IS NOT NULL OR b.geometry IS NOT NULL
ORDER BY b.name, b.id LIMIT 10;

DO $$
BEGIN
    IF (SELECT count(*) FROM gap_test_profiles) <> 10 THEN
        RAISE EXCEPTION 'Need at least 10 mapped barangays. Run BarangaySeeder first.';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM ict_components WHERE is_active AND component_type='hardware' AND counts_toward_infrastructure)
       OR NOT EXISTS (SELECT 1 FROM ict_components WHERE is_active AND component_type='software')
       OR NOT EXISTS (SELECT 1 FROM ict_components WHERE is_active AND component_type='network' AND counts_toward_infrastructure) THEN
        RAISE EXCEPTION 'Run InfrastructureComponentSeeder first (hardware, software, network required).';
    END IF;
END $$;

-- Refuse name collisions with institutions not owned by this fixture.
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM gap_test_profiles p
        CROSS JOIN (VALUES ('School'), ('Barangay Hall')) AS kind(type)
        JOIN institutions i ON i.barangay_id=p.barangay_id
          AND lower(trim(i.name))=lower('TEST GAP V1 - ' || p.barangay_name || ' - ' || kind.type)
        WHERE i.notes IS DISTINCT FROM 'Synthetic testing fixture: gap-analysis-10-barangays-v1'
    ) THEN RAISE EXCEPTION 'A test institution name is already used by another record. No changes applied.';
    END IF;
END $$;

-- Imported/manual IDs can leave PostgreSQL sequences behind existing rows.
-- Lock the affected tables while advancing sequences; never move them backward.
LOCK TABLE institutions, ict_inventory, institution_internet_connections,
    institution_analytics IN SHARE ROW EXCLUSIVE MODE;
DO $$
DECLARE
    target record;
    sequence_name text;
    highest_id bigint;
    sequence_value bigint;
BEGIN
    FOR target IN SELECT * FROM (VALUES
        ('institutions', 'id'), ('ict_inventory', 'asset_id'),
        ('institution_internet_connections', 'id'), ('institution_analytics', 'id')
    ) AS targets(table_name, column_name)
    LOOP
        sequence_name := pg_get_serial_sequence(target.table_name, target.column_name);
        IF sequence_name IS NULL THEN
            RAISE EXCEPTION 'No owned ID sequence found for %.%', target.table_name, target.column_name;
        END IF;
        EXECUTE format('SELECT max(%I) FROM %I', target.column_name, target.table_name) INTO highest_id;
        EXECUTE format('SELECT last_value FROM %s', sequence_name::regclass) INTO sequence_value;
        IF highest_id IS NOT NULL AND highest_id >= sequence_value THEN
            PERFORM setval(sequence_name::regclass, highest_id, TRUE);
        END IF;
    END LOOP;
END $$;

-- Track only newly created institutions so reruns cannot overwrite edited data.
CREATE TEMP TABLE gap_test_new (institution_id bigint PRIMARY KEY) ON COMMIT DROP;
WITH inserted AS (
    INSERT INTO institutions (
        name, type, barangay_id, latitude, longitude, location_geojson,
        student_population, staff_population, has_computer_lab,
        has_internet_connection, isp_provider, isp_connection_plan,
        has_backup_internet_link, has_public_access_network,
        bandwidth_allocation_mbps, download_speed_mbps, upload_speed_mbps,
        latency_ms, throughput_mbps, internet_speed, q10_number_of_connections,
        notes, is_active, requires_audit, created_at, updated_at
    )
    SELECT 'TEST GAP V1 - ' || p.barangay_name || ' - ' || kind.type,
        kind.type, p.barangay_id, ST_Y(p.point), ST_X(p.point), ST_AsGeoJSON(p.point)::jsonb,
        CASE WHEN kind.type='School' THEN 200+p.severity*40 ELSE 0 END,
        CASE WHEN kind.type='School' THEN 15+p.severity ELSE 12+p.severity END,
        kind.type='School' AND p.severity<9,
        p.severity<>9, CASE WHEN p.severity<>9 THEN 'Synthetic Test ISP' END,
        CASE WHEN p.severity<>9 THEN 'Fiber 100 Mbps' END,
        FALSE, p.severity<=4,
        CASE WHEN p.severity<>9 THEN 100 END,
        CASE WHEN p.severity<>9 THEN greatest(0, 100-(p.severity-1)*11) END,
        CASE WHEN p.severity<>9 THEN greatest(1, 50-(p.severity-1)*5) END,
        CASE WHEN p.severity<>9 THEN 10+p.severity*8 END,
        CASE WHEN p.severity<>9 THEN greatest(0, 95-(p.severity-1)*10) END,
        CASE WHEN p.severity<>9 THEN greatest(0, 100-(p.severity-1)*11) END,
        CASE WHEN p.severity=9 THEN 0 ELSE 1 END,
        'Synthetic testing fixture: gap-analysis-10-barangays-v1', TRUE, TRUE, NOW(), NOW()
    FROM gap_test_profiles p
    CROSS JOIN (VALUES ('School'), ('Barangay Hall')) AS kind(type)
    WHERE NOT EXISTS (
        SELECT 1 FROM institutions i WHERE i.barangay_id=p.barangay_id
        AND lower(trim(i.name))=lower('TEST GAP V1 - ' || p.barangay_name || ' - ' || kind.type)
    )
    RETURNING id
)
INSERT INTO gap_test_new SELECT id FROM inserted;

-- One fully recorded response for every active catalogue component, including
-- explicit zeros. Active + Retired = Total; Functional + Defective = Active.
WITH quantities AS (
    SELECT i.id AS institution_id, p.severity, c.id AS id_component, c.key, c.name, c.component_type, c.is_computing_device, c.subsection,
        CASE
          WHEN c.component_type='connectivity' THEN
            CASE WHEN c.key='isp_connection_plan' AND p.severity<>9 THEN 1
                 WHEN c.key='public_access_network' AND p.severity<=4 THEN 1 ELSE 0 END
          WHEN c.key IN ('desktop_computer','laptop','windows_os','productivity_suite','anti_virus')
            THEN CASE WHEN i.type='School' THEN 20 ELSE 10 END
          WHEN c.key IN ('router','network_switch','wireless_access_point','lan_capable_printer','ups','lan_cable')
            THEN CASE WHEN i.type='School' THEN 5 ELSE 3 END
          ELSE 0
        END AS units
    FROM gap_test_new n JOIN institutions i ON i.id=n.institution_id
    JOIN gap_test_profiles p ON p.barangay_id=i.barangay_id
    CROSS JOIN ict_components c
    WHERE c.is_active AND c.component_type IN ('hardware','software','network','connectivity')
), retired AS (
    SELECT q.*, CASE WHEN component_type='connectivity' THEN 0
         WHEN severity=10 THEN units ELSE floor(units*(severity-1)*0.07)::integer END AS retired_units
    FROM quantities q
), available AS (
    SELECT r.*, units-retired_units AS active_units,
        CASE WHEN component_type='connectivity' THEN 0
             ELSE floor((units-retired_units)*(severity-1)*0.09)::integer END AS defective_units
    FROM retired r
)
INSERT INTO ict_inventory (
    institution_id, component_id, category, asset_name, component_type,
    is_computing_device, quantity, condition, status, acquisition_year,
    total_count, active_count, retired_count, functional_count, defective_count,
    functional_count_recorded, needs_maintenance, specifications, notes, created_at, updated_at
)
SELECT institution_id, id_component, key, name, component_type, is_computing_device,
    units, CASE WHEN defective_units>0 THEN 'needs_repair' ELSE 'good' END,
    CASE WHEN units>0 AND active_units=0 THEN 'retired' ELSE 'active' END,
    CASE WHEN severity>=7 THEN 2016 ELSE 2024 END,
    units, active_units, retired_units, active_units-defective_units, defective_units,
    TRUE, defective_units>0,
    jsonb_build_object('inventory_subsection',subsection),
    'Synthetic testing fixture: gap-analysis-10-barangays-v1', NOW(), NOW()
FROM available;

INSERT INTO institution_internet_connections (
    institution_id, connection_number, isp, plan, allocation, validity, context,
    download_speed_mbps, upload_speed_mbps, latency_ms, throughput_mbps, created_at, updated_at
)
SELECT i.id, 1, 'Synthetic Test ISP', 'Fiber 100 Mbps', 'Institution-wide',
    'Monthly', 'Synthetic test measurement', i.download_speed_mbps, i.upload_speed_mbps,
    i.latency_ms, i.throughput_mbps, NOW(), NOW()
FROM gap_test_new n JOIN institutions i ON i.id=n.institution_id
WHERE i.q10_number_of_connections=1;

-- Same default scope and two-decimal rounding as IctGapBaselineCalculator:
-- Infrastructure = Defective / Active * 100 (all retired => 100).
-- Connectivity = max(100 - measured download, 0) / 100 * 100; no service => 100.
-- Obsolescence = Retired / Total * 100; Gap = mean of the three rounded scores.
WITH totals AS (
    SELECT i.id AS institution_id, sum(v.total_count) AS total,
        sum(v.active_count) AS active, sum(v.retired_count) AS retired,
        sum(v.defective_count) AS defective
    FROM gap_test_new n JOIN institutions i ON i.id=n.institution_id
    JOIN ict_inventory v ON v.institution_id=i.id
    JOIN ict_components c ON c.id=v.component_id
    WHERE v.component_type='software'
       OR (v.component_type IN ('hardware','network') AND c.counts_toward_infrastructure)
    GROUP BY i.id
), scores AS (
    SELECT t.*,
        CASE WHEN active>0 THEN round(defective*100.0/active,2)
             WHEN total>0 AND retired=total THEN 100.00 END AS infrastructure,
        CASE WHEN i.q10_number_of_connections=0 THEN 100.00
             ELSE round(greatest(100-i.download_speed_mbps,0),2) END AS connectivity,
        round(retired*100.0/nullif(total,0),2) AS obsolescence
    FROM totals t JOIN institutions i ON i.id=t.institution_id
)
INSERT INTO institution_analytics (
    institution_id, gap_score, infrastructure_deficit_score, connectivity_divide_score,
    technological_obsolescence_score, risk_level, infrastructure_narrative,
    policy_recommendation, ai_priorities, recommendations_detail, analysis_date,
    processing_status, processing_stage, processing_completed_at, created_at, updated_at
)
SELECT s.institution_id, round((infrastructure+connectivity+obsolescence)/3,2),
    infrastructure, connectivity, obsolescence, 'assessed', NULL, NULL, NULL,
    jsonb_build_object(
        'synthetic_test_data',TRUE, 'score_source','sql_deterministic_test_fixture',
        'python_service_used',FALSE,
        'decision_support_version','4.1-advertised-speed-shortfall',
        'calculation_scope',jsonb_build_object('include_software',TRUE,
            'inventory_types',jsonb_build_array('hardware','software','network'),
            'label','Hardware + Software + Network'),
        'connectivity_assessment', jsonb_build_object(
            'method','advertised-speed-shortfall-v1',
            'status',CASE WHEN i.q10_number_of_connections=0 THEN 'no_service' ELSE 'complete' END,
            'score',connectivity, 'reported_connection_count',i.q10_number_of_connections,
            'recorded_connection_count',i.q10_number_of_connections,
            'valid_connection_count',i.q10_number_of_connections,'missing_connection_count',0,
            'reason_codes','[]'::jsonb,
            'plan_counts',jsonb_build_object('identifiable',i.q10_number_of_connections,
                'price_only',0,'ambiguous',0,'not_recorded',0),
            'connections',CASE WHEN i.q10_number_of_connections=0 THEN '[]'::jsonb
                ELSE jsonb_build_array(jsonb_build_object('connection_number',1,
                    'plan_classification','identifiable','plan_reason',NULL,
                    'advertised_download_mbps',100,'download_speed_mbps',i.download_speed_mbps,
                    'upload_speed_mbps',i.upload_speed_mbps,'latency_ms',i.latency_ms,
                    'throughput_mbps',i.throughput_mbps,'shortfall_percent',connectivity,
                    'reason_codes','[]'::jsonb)) END
        )
    ), NOW(), 'completed', 'completed', NOW(), NOW(), NOW()
FROM scores s JOIN institutions i ON i.id=s.institution_id;

-- Synchronize only this fixture's derived inventory summaries (SQL skips observers).
UPDATE institutions i SET
    number_of_computers=(SELECT coalesce(sum(functional_count),0) FROM ict_inventory
        WHERE institution_id=i.id AND is_computing_device),
    needs_maintenance=EXISTS(SELECT 1 FROM ict_inventory WHERE institution_id=i.id AND defective_count>0)
FROM gap_test_new n WHERE i.id=n.institution_id;

COMMIT;

-- Expected on a fresh run: 10 barangays, 20 institutions, 20 complete analyses.
SELECT b.name AS barangay, count(*) AS test_institutions,
    round(avg(a.infrastructure_deficit_score),2) AS infrastructure_gap,
    round(avg(a.connectivity_divide_score),2) AS connectivity_gap,
    round(avg(a.technological_obsolescence_score),2) AS obsolescence_gap,
    round(avg(a.gap_score),2) AS mean_gap_score
FROM institutions i JOIN barangays b ON b.id=i.barangay_id
JOIN institution_analytics a ON a.institution_id=i.id
WHERE i.notes='Synthetic testing fixture: gap-analysis-10-barangays-v1'
GROUP BY b.id,b.name ORDER BY mean_gap_score,b.name;
