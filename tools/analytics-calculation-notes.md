# TALASTAS explicit analytics inputs — local implementation

This change adds explicit recorded inputs. No migration was run against a local or production database, no institution/inventory/connection records were changed, and no import/backfill/recalculation was run. Existing SQL exports were not rewritten.

## Definition and formulas

For newly encoded data, Active means deployed/in-service and may include defective units. Defective within Active is explicitly recorded inside that population. General Defective and Functional remain independent responses. Historical Active/Functional/Defective values are never relabeled, copied, or inferred.

- Infrastructure = SUM(defective_active_count) / SUM(active_count) * 100. Both inputs must be complete and valid across included rows; Active must be positive. Defective within Active cannot exceed Active, Total, or recorded general Defective.
- Retirement proxy = SUM(retired_count) / SUM(total_count) * 100 with valid recorded required inputs and positive Total. It is not an equipment-age measure.
- Connectivity = equal mean of per-connection rounded max(advertised - measured, 0) / advertised * 100. Each expected connection needs valid inputs. Explicit recorded advertised Mbps takes priority; otherwise a single unambiguous unit-bearing plan speed is parsed. Conflicting explicit/parsed values are invalid. Plan text remains intact. Prices, bare numbers, and legacy institution bandwidth are never used as advertised speed in the job.
- Overall = (infrastructure + connectivity + retirement) / 3 only when all three are available. Scores use the existing two-decimal half-up rounding.

Unrelated Functional warnings do not suppress valid indicators. Unknown counts and missing included rows remain unavailable for their dependent indicator. The established-applicability missing_expected_inventory payload can identify known missing coverage; no catalog-wide requirements are invented. Current persisted schema does not establish which entirely absent categories are required, so jobs explicitly identify coverage as saved_applicable_rows.

## Changes and locations

- `database/migrations/2026_11_02_000001_add_explicit_analytics_inputs.php`: nullable integer ict_inventory.defective_active_count and nullable decimal(14,6) institution_internet_connections.advertised_download_mbps. No default zero, no backfill, existing column order preserved.
- `app/Models/IctInventory.php`, `InstitutionInternetConnection.php`, `app/Services/InventoryCounts.php`, `app/Http/Controllers/IctInventoryController.php`, `PortalIctInventoryController.php`, `public/js/inventory-counts.js`, inventory Blade inputs/details: model, unified/single-item forms, inherited assigned-institution CRUD and existing API intake, validation, raw Functional provenance preservation, display.
- `app/Services/InternetConnectionChanges.php`: explicitly merge partial updates and remove only requested connection numbers, validate state/speed conflicts, and save under the caller's transaction/institution lock. Omission does not delete a connection. Explicit service status and reported count are exposed in Q10.
- `app/Services/IctGapBaselineCalculator.php`, `InternetConnectivityAssessment.php`, Python schemas/modeling/main: independent per-indicator validation, complete input metadata, partial results, conservative plan parsing, parity of scores and reasons.
- `app/Jobs/ProcessInstitutionAnalytics.php`, `app/Observers/IctInventoryObserver.php`: new inputs in shared-scope payloads/change detection/source recheck; existing tokens and publication guards retained. Connection observer already invalidates on edits/removal. Persisted numbers are locally verified and correctly labeled `laravel_deterministic`; python_service_used separately records whether Python responded.
- Analytics controller and institution display permit connectivity-only partial calculation, show the existing software scope choices and indicator-specific reasons. AI output cannot override the numerical score fields; ordinary AI exceptions preserve deterministic publication.

Existing SQL imports that omit these new nullable columns still leave them NULL. Raw SQL does not run Laravel validation/invalidation. This fix does not authorize imports or automatically recalculate data.

## Local activation commands (not executed)

First ensure the command targets the intended local database. The audited loopback PostgreSQL server did not support SSL; an SSL-required local connection must be corrected locally before migration commands will work. Do not change remote/production connection settings for this.

```powershell
php artisan config:clear
php artisan migrate --path=database/migrations/2026_11_02_000001_add_explicit_analytics_inputs.php
php artisan queue:restart
```

The queue worker must be running; use the existing local worker process, or start `php artisan queue:work` if none is running. Restart a long-running PHP application server if applicable.

Restart the existing Python service using its existing interpreter and port. The interpreter used successfully for tests here was:

```powershell
& 'C:\Users\Nawinaka\AppData\Local\Python\pythoncore-3.14-64\python.exe' -m uvicorn main:app --app-dir python_service --port 8002
```

The checked interpreter has FastAPI 0.103.2, Uvicorn 0.23.2 and Pydantic 2.13.5. Endpoint tests passed, but FastAPI and Uvicorn are older than the repository requirements (`>=0.115` and `>=0.30`, respectively). Use the application's normal environment and `python_service/requirements.txt` when preparing its service environment. No Python packages were installed for this task.

`npm run build` was executed successfully. Rebuild assets after future frontend edits. None of the activation commands above was executed.

## Verification

No tests load the application's .env or use a persistent database. Workflow fixtures are created directly in SQLite :memory:; no migration runner is invoked.

- `php tools/verify-explicit-analytics.php`: 31 calculation/encoding/scope checks.
- `php tools/verify-explicit-analytics-workflow.php`: 16 isolated workflow checks: real job persistence, AI failure/numeric override protection, stale tokens, source mutation, field invalidation, transactional connection edits/removal/rollback, API intake/null/omission and historical Functional provenance.
- `python tools/verify-explicit-analytics-parity.py`: 72 full-result PHP/Python parity cases, real Pydantic/API responses, and four ASGI request/response-boundary checks without sockets/network.
- `node tools/verify-explicit-inventory-ui.mjs`: six browser-side validation checks and inline inventory JavaScript syntax.
- `php tools/verify-analytics-views.php`: 19 Blade templates compile and pass PHP syntax checks. Temporary compiled files are removed by the script.
- Existing calculation (17 checks plus scope), mocked Python failure (3), formula parity (10), and inventory ordering (6) checks retained and passed. The ordering fixture was extended for the sixth inventory input.
- Changed PHP source syntax, Python syntax, git whitespace checks, and Vite build passed.

The checks do not replace a visual browser review or a real concurrent multi-worker PostgreSQL test. Source mutation and token scenarios were verified with isolated deterministic fixtures. No live Python/AI service calls were made.

## Institution 76 still needs verified data

- Explicit defective_active_count for every included saved inventory row, including confirmed zeros. Check the recorded Active population against the newly documented deployed/in-service definition; normalization of Active + Retired is not evidence of service status or Defective Active membership.
- Verified advertised_download_mbps for Internet connection 1, or a verified unambiguous unit-bearing plan speed. Keep plan text `2000` until corrected from evidence; do not infer 2000 Mbps or use the legacy 50 Mbps value.
- Existing measured download 0.54 Mbps remains unchanged.

Its existing 3.41% retirement proxy is not overwritten by implementing this change. Overall remains unavailable until all required defined inputs are actually recorded and the explicit Calculate workflow is run.

## Unresolved policy questions (preserved)

Software follows include_software regardless of its component flag. Hardware/network use counts_toward_infrastructure; connectivity inventory is excluded. Institution component exclusions and component active flags are not newly applied. The same filtered rows feed every inventory numerator and denominator. Whether these policies should change remains a methodology decision. No measurement-freshness threshold or new applicability rule has been invented. Defective/Functional/Retired historical source verification remains a data stewardship task.
