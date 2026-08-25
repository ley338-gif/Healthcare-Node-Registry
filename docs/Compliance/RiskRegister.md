# Risk Register

| ID | Risiko | Wahrscheinlichkeit | Auswirkung | Behandlung | Owner | Status |
|---|---|---:|---:|---|---|---|
| R-001 | Scope wächst vor MVP unkontrolliert | mittel | hoch | Scope-Gate und Roadmap | Product Owner | offen |
| R-002 | Falsche fachliche DICOM-Modellierung | mittel | hoch | Fachreview und Pilot | Healthcare Lead | offen |
| R-003 | Unbefugter Zugriff auf Infrastrukturinfos | mittel | hoch | RBAC, verpflichtende MFA für privilegierte Rechte, Break-Glass-/Offboarding-Prozess, Audit | Security / Identity Owner | technisch behandelt; Pilot-Rechteabnahme offen |
| R-004 | Schadsoftware in Dokumentenuploads | mittel | hoch | Quarantäne, Prüfung, Scan-Adapter | Security | offen |
| R-005 | Backup ist nicht wiederherstellbar oder verletzt RPO/RTO | niedrig-mittel | hoch | RPO 24 h, RTO 8 h, getrenntes verschlüsseltes Ziel, quartalsweiser Restore-Test | Operations | Zielhostnachweis offen |
| R-006 | Abhängigkeit oder Basisimage wird nicht gepflegt | mittel | mittel-hoch | Lockfiles, Dependency- und Container-Gates, SBOM, wöchentlicher Rescan | Lead Dev / Security | behandelt, laufend |
| R-007 | UI suggeriert Live-Monitoring | mittel | mittel | klare Statussemantik | UX | offen |
| R-008 | ISO-Readiness wird als Zertifizierung vermarktet | niedrig | hoch | Freigabeprozess für Claims | Management | offen |
| R-009 | Einzelentwickler-Bus-Faktor | hoch | hoch | Dokumentation, Automatisierung | Project Owner | offen |
| R-010 | Referenzbilder führen zu überladenem MVP | mittel | mittel | Funktionsscope vor Optik | Product Owner | offen |
| R-011 | Aktiver DICOM-Test verändert ein Zielsystem oder bleibt unbereinigt | mittel | hoch | Einzelrechte, Pflichtbestätigung, synthetische Daten, Testfenster, Cleanup-Owner und Audit | Healthcare Lead / Target Owner | Healthcare-E2E-Abnahme offen |
| R-012 | Betriebsstörung oder Zertifikatsablauf bleibt unbemerkt | mittel | hoch | Health-, Speicher-, Backup-, Zertifikats- und Security-Alarmwege mit Testquittierung | Operations / Security | Zielintegration offen |

## Bewertung

Vor Projektstart werden Skalen, Akzeptanzkriterien, Reviewrhythmus und Eskalationsschwellen festgelegt.
