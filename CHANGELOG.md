# Changelog

## 0.11.0
- Added installation-specific profiles and per-resource direction controls.
- Added Envision Pathology Billing preset.
- Added profile-specific capability responses and route/workflow enforcement.
- Added profile REST endpoints and administrator profile viewer.


## 0.10.0
- Added reconciliation against native OpenEMR records.
- Added dead-letter queue, sweep, replay, and manual resolution.
- Added operational metrics and production diagnostics endpoints.
- Added CLI operations utility.


## 0.9.0
- Added end-to-end integration workflow orchestration.
- Added persistent queue, step history, exponential retries, manual retry, and cancellation.
- Added one workflow spanning patient, encounter, order, results, documents, and billing.
- Added DORN-style positive/negative acknowledgment state and a CLI queue worker.


## 0.8.0
- Added feature-gated native CPT/HCPCS charge synchronization.
- Added billing validation, permanent charge links, and idempotency.
- New charges remain unbilled and unprocessed for fee-sheet review.


## 0.7.0
- Added PDF/binary document synchronization.
- Added FHIR DocumentReference write support.
- Added permanent document links to procedure reports.
- Added content hashing, idempotency, and amendment-safe behavior.


## 0.6.0
- Added native procedure reports and results.
- Added FHIR Observation and DiagnosticReport writes.
- Added HL7 ORU normalization and ACK responses using DORN-compatible fields.
- Added immutable finalized-report handling and result links.


## 0.5.0
- Added native procedure order creation for FHIR ServiceRequest and Standard API payloads.
- Added DORN-aligned order, code, specimen, and forms relationships.
- Added duplicate-safe order links and dry-run validation.


## 0.2.0
- Adopted OpenEMR 8.2/DORN-style module lifecycle.
- Added FHIR, Standard API, and HL7 v2 transport adapters.
- Added canonical message model, queue, idempotency, hashing, and audit.
- Added capability endpoint and module GUI.
- Added feature-gated native writer interfaces.

## 0.1.0
- Initial staging bridge.
