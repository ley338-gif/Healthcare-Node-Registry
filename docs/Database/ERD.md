# Initiales fachliches ERD

```mermaid
erDiagram
    ORGANIZATIONS ||--o{ SITES : contains
    SITES ||--o{ DEPARTMENTS : contains
    SITES ||--o{ ASSETS : hosts
    DEPARTMENTS ||--o{ ASSETS : owns
    ASSETS ||--o{ ENDPOINTS : exposes
    ENDPOINTS ||--o| DICOM_ENDPOINT_DETAILS : specializes
    ENDPOINTS ||--o| HL7_ENDPOINT_DETAILS : specializes
    ENDPOINTS ||--o| FHIR_ENDPOINT_DETAILS : specializes
    ENDPOINTS ||--o{ DICOM_SERVICE_ROLES : supports
    ENDPOINTS ||--o{ CONNECTIONS : source
    ENDPOINTS ||--o{ CONNECTIONS : target
    ASSETS ||--o{ RESPONSIBILITY_ASSIGNMENTS : assigned
    CONTACTS ||--o{ RESPONSIBILITY_ASSIGNMENTS : responsible
    ASSETS ||--o{ DOCUMENT_LINKS : has
    DOCUMENTS ||--o{ DOCUMENT_LINKS : linked
    USERS ||--o{ AUDIT_EVENTS : performs
    ASSETS ||--o{ ASSET_TAGS : tagged
    TAGS ||--o{ ASSET_TAGS : classifies

    ORGANIZATIONS {
      bigint id PK
      uuid public_id UK
      string name
      string documentation_state
      timestamps
    }
    SITES {
      bigint id PK
      uuid public_id UK
      bigint organization_id FK
      string name
      string code
      string timezone
      timestamps
    }
    DEPARTMENTS {
      bigint id PK
      uuid public_id UK
      bigint site_id FK
      string name
      timestamps
    }
    ASSETS {
      bigint id PK
      uuid public_id UK
      bigint site_id FK
      bigint department_id FK
      string name
      string asset_type
      string manufacturer
      string model
      string software_version
      string lifecycle_status
      string documentation_state
      text notes
      timestamps
    }
    ENDPOINTS {
      bigint id PK
      uuid public_id UK
      bigint asset_id FK
      string protocol
      string name
      inet ip_address
      string hostname
      int port
      string documentation_state
      string information_source
      timestamp verified_at
      timestamps
    }
    DICOM_ENDPOINT_DETAILS {
      bigint endpoint_id PK_FK
      string ae_title
      string tls_mode
      string character_set_note
    }
    DICOM_SERVICE_ROLES {
      bigint id PK
      bigint endpoint_id FK
      string service
      boolean supports_scu
      boolean supports_scp
      string called_ae_override
      string calling_ae_override
    }
    HL7_ENDPOINT_DETAILS {
      bigint endpoint_id PK_FK
      string transport
      string direction
      string charset
      string message_context
    }
    FHIR_ENDPOINT_DETAILS {
      bigint endpoint_id PK_FK
      string base_url
      string auth_method
      string capability_status
    }
    CONNECTIONS {
      bigint id PK
      uuid public_id UK
      bigint source_endpoint_id FK
      bigint target_endpoint_id FK
      string service
      string purpose
      string documentation_state
      string operational_intent
      string information_source
      timestamp verified_at
      timestamps
    }
    CONTACTS {
      bigint id PK
      uuid public_id UK
      string contact_type
      string display_name
      string email
      string phone
      string organization_name
      timestamps
    }
    RESPONSIBILITY_ASSIGNMENTS {
      bigint id PK
      bigint asset_id FK
      bigint contact_id FK
      string responsibility_type
      date valid_from
      date valid_until
    }
    DOCUMENTS {
      bigint id PK
      uuid public_id UK
      string original_name
      string storage_key UK
      string media_type
      bigint size_bytes
      string sha256
      string classification
      string scan_status
      timestamps
    }
    DOCUMENT_LINKS {
      bigint id PK
      bigint document_id FK
      bigint asset_id FK
      string category
      date valid_until
      timestamps
    }
    AUDIT_EVENTS {
      bigint id PK
      uuid event_id UK
      bigint actor_user_id FK
      string actor_type
      string action
      string subject_type
      uuid subject_public_id
      uuid correlation_id
      jsonb before_data
      jsonb after_data
      inet source_ip
      timestamp created_at
    }
```

## Verbindliche Regeln

- interne IDs und öffentliche IDs werden durch ADR-0004 festgelegt
- Ports: 1 bis 65535
- Zeitstempel: UTC
- AE Titles werden fachlich validiert
- JSONB nur für seltene Erweiterungen, nicht für Kernattribute
- Connections referenzieren konkrete Endpoints
- SCU-/SCP-Rollen werden pro DICOM-Dienst modelliert
- Audit Events sind append-only
- `actor_user_id` darf bei Systemaktionen leer sein; `actor_type` bleibt erforderlich
