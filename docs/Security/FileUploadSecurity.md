# File Upload Security

## Grundprinzipien

- Speicherung außerhalb des Webroots
- zufällige, nicht benutzerkontrollierte Storage Keys
- Originalname nur als Metadatum
- Größenlimit
- Endungs- und Inhalts-MIME-Prüfung
- Allowlist statt Blocklist
- SHA-256
- Quarantäne vor Freigabe
- optionaler Malware-Scanner über Adapter
- Download-Audit
- sichere Response Header

## Standardverhalten

Wenn ein verpflichtender Malware-Scanner nicht erreichbar ist, wird die Datei nicht automatisch freigegeben. Das konkrete Fail-Open-/Fail-Closed-Verhalten muss je Installation dokumentiert werden; Default ist Fail Closed.

## Besonders riskante Formate

HTML, SVG, ausführbare Dateien, Skripte und aktive Office-Inhalte werden im MVP nicht standardmäßig erlaubt.

## Archive

ZIP und andere Archive werden nur nach gesonderter Freigabe unterstützt. Schutz vor Zip Bombs, Pfadtraversal und verschachtelten Archiven ist erforderlich.

## Download

- `Content-Disposition: attachment`
- sicherer, bereinigter Dateiname
- `X-Content-Type-Options: nosniff`
- keine direkte öffentliche Storage-URL
- Berechtigungsprüfung bei jedem Download
