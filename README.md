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

Version 0.2.0 queues and audits inbound messages. Native writes into
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
