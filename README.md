# OpenEMR NeoLIMS Hybrid Bridge

Target: OpenEMR 8.2 (`rel-820`)

This module follows the first-party DORN lifecycle pattern while supporting:

- FHIR create/update transport
- OpenEMR Standard API transport
- HL7 v2 ORM/ORU/ACK transport
- canonical message normalization
- stable identifiers and idempotent upserts
- payload hashing and audit history
- capability negotiation
- raw payload retention
- feature-gated native OpenEMR writes

## Current safety state

Version 0.6.0 queues and audits inbound messages. Native writes into
`procedure_order`, `procedure_result`, `procedure_report`, documents, and
billing are intentionally disabled until each mapping has integration tests.

## Install from GitHub

```bash
cd /var/www/openemr/interface/modules/custom_modules
sudo git clone https://github.com/pathcoder4408/openemr-neolims-bridge.git
sudo chown -R www-data:www-data openemr-neolims-bridge
```

Then install and enable in **Modules → Manage Modules**.

## API routes

- `GET /apis/default/api/neolims/capabilities`
- `GET /apis/default/api/neolims/messages`
- `GET /apis/default/api/neolims/messages/{uuid}`
- `POST /apis/default/api/neolims/messages`
- `POST /apis/default/api/neolims/hl7`

FHIR POST/PUT routes:

- ServiceRequest
- Specimen
- Observation
- DiagnosticReport
- Procedure
- DocumentReference
- Provenance

## OAuth scopes

- `user/neolims_capability.s`
- `user/neolims_message.crus`
- `user/neolims_hl7.cs`
- `user/ServiceRequest.crus`
- `user/Specimen.crus`
- `user/Observation.crus`
- `user/DiagnosticReport.crus`
- `user/Procedure.crus`
- `user/DocumentReference.crus`
- `user/Provenance.crus`


## Phase 5: Procedure orders
Adds `/api/neolims/order/validate`, `/api/neolims/order/sync`, order links, and native OpenEMR procedure-order creation. Native writes remain feature-gated.


## Phase 6: Results and reports

Adds native OpenEMR `procedure_report` and `procedure_result` synchronization from Standard API payloads, FHIR Observation/DiagnosticReport, and HL7 ORU messages. Finalized reports are immutable; corrections create a new report revision.


## Phase 7
Adds OpenEMR document storage and FHIR DocumentReference imports linked to native procedure reports.


## Phase 8 billing
Creates reviewable OpenEMR billing rows only when both native writes and billing writes are enabled. It does not submit claims.


## Phase 9: workflow orchestration

Phase 9 provides one idempotent workflow envelope for the complete accession export:
patient, encounter, order, result/report, documents, and billing. Each workflow has a
stable external ID, persistent status, current step, event history, retry count,
exponential retry schedule, and final acknowledgment.

Routes:

```text
POST /apis/default/api/neolims/workflows
GET  /apis/default/api/neolims/workflows
GET  /apis/default/api/neolims/workflows/{uuid}
POST /apis/default/api/neolims/workflows/{uuid}/run
POST /apis/default/api/neolims/workflows/{uuid}/retry
POST /apis/default/api/neolims/workflows/{uuid}/cancel
```

Worker:

```bash
sudo -u www-data env OPENEMR_ROOT=/var/www/openemr OPENEMR_SITE=default \
  php /var/www/openemr/interface/modules/custom_modules/openemr-neolims-bridge/bin/neolims-worker.php 25
```

The worker is safe to run from cron or a systemd timer. A completed workflow returns
an `AA` acknowledgment. Failed workflows retain the step and error and are retried
with exponential backoff until `max_attempts` is reached.


## Phase 10: production operations

Adds reconciliation, dead-letter handling, replay, metrics, and operational diagnostics.

Routes:

```text
GET  /apis/default/api/neolims/operations/metrics
POST /apis/default/api/neolims/operations/reconcile
GET  /apis/default/api/neolims/operations/reconciliation-runs
GET  /apis/default/api/neolims/operations/dead-letters
POST /apis/default/api/neolims/operations/dead-letters/{uuid}/replay
POST /apis/default/api/neolims/operations/dead-letters/{uuid}/resolve
POST /apis/default/api/neolims/operations/dead-letters/sweep
```

CLI:

```bash
php bin/neolims-operations.php metrics
php bin/neolims-operations.php reconcile [connection_key]
php bin/neolims-operations.php sweep
php bin/neolims-operations.php replay <workflow_uuid>
```


## Phase 11: Installation Profiles

The Envision Billing preset receives patient, insurance, encounter, documents, and billing data. Orders and structured results are disabled by default. Every API and workflow operation is checked against the active installation profile.
