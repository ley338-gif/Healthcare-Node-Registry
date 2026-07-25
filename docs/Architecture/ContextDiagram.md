# Systemkontext

```mermaid
flowchart LR
    Admin[PACS/RIS/KIS-/IT-Administrator]
    Auditor[Auditor / ISB]
    Browser[Browser]
    Proxy[Reverse Proxy / TLS]
    App[Healthcare Node Registry]
    DB[(PostgreSQL)]
    Storage[(Dokumentenspeicher)]
    Scanner[Optionaler Malware-Scanner]
    Backup[(Backup-Ziel)]
    Directory[Später: LDAP/OIDC]
    Networks[Später: getrennte Healthcare-Netze]

    Admin --> Browser
    Auditor --> Browser
    Browser -->|HTTPS| Proxy
    Proxy --> App
    App --> DB
    App --> Storage
    Storage -. optional .-> Scanner
    DB --> Backup
    Storage --> Backup
    App -. spätere Authentisierung .-> Directory
    App -. keine aktive Kommunikation im MVP .-> Networks
```

## Trust Boundaries

1. Benutzergerät zu Reverse Proxy
2. Reverse Proxy zu Anwendung
3. Anwendung zu PostgreSQL
4. Anwendung zu Dokumentenspeicher
5. optionaler Scanner
6. Backup-Ziel
7. spätere Verzeichnisdienste oder Netzwerkagenten

Das MVP führt keine ungefragten Netzwerk- oder DICOM-Scans aus.
