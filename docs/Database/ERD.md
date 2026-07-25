# Initiales ERD

```mermaid
erDiagram
    ORGANIZATIONS ||--o{ SITES : contains
    SITES ||--o{ DEPARTMENTS : contains
    SITES ||--o{ ASSETS : hosts
    DEPARTMENTS ||--o{ ASSETS : owns
    ASSETS ||--o{ ENDPOINTS : exposes
    ENDPOINTS ||--o{ CONNECTIONS : source
    ENDPOINTS ||--o{ CONNECTIONS : target
    ASSETS ||--o{ DOCUMENT_LINKS : has
    DOCUMENTS ||--o{ DOCUMENT_LINKS : linked
    USERS ||--o{ AUDIT_EVENTS : performs
    ASSETS ||--o{ ASSET_TAGS : tagged
    TAGS ||--o{ ASSET_TAGS : classifies

    ORGANIZATIONS {
      uuid id PK
      string name
      string status
      timestamps
    }
    SITES {
      uuid id PK
      uuid organization_id FK
      string name
      string code
      string timezone
      timestamps
    }
    DEPARTMENTS {
      uuid id PK
      uuid site_id FK
      string name
      timestamps
    }
    ASSETS {
      uuid id PK
      uuid site_id FK
      uuid department_id FK
      string name
      string asset_type
      string manufacturer
      string model
      string software_version
      string lifecycle_status
      text notes
      timestamps
    }
    ENDPOINTS {
      uuid id PK
      uuid asset_id FK
      string protocol
      string name
      inet ip_address
      string hostname
      int port
      string ae_title
      jsonb configuration
      timestamps
    }
    CONNECTIONS {
      uuid id PK
      uuid source_endpoint_id FK
      uuid target_endpoint_id FK
      string service
      string status
      text purpose
      jsonb configuration
      timestamps
    }
    DOCUMENTS {
      uuid id PK
      string original_name
      string storage_key
      string media_type
      bigint size_bytes
      string sha256
      string classification
      timestamps
    }
    DOCUMENT_LINKS {
      uuid id PK
      uuid document_id FK
      uuid asset_id FK
      string category
      date valid_until
      timestamps
    }
    AUDIT_EVENTS {
      uuid id PK
      uuid user_id FK
      string action
      string subject_type
      uuid subject_id
      jsonb before_data
      jsonb after_data
      inet source_ip
      timestamp created_at
    }
```

## Hinweis

Das ERD ist ein Ausgangspunkt. Vor Implementierung werden Primärschlüssel, Mehrmandantenfähigkeit, Soft Deletes, Kontaktmodell und Audit-Aufbewahrung als ADR geklärt.
