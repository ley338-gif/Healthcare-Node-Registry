# Security Policy

## Unterstützte Versionen

Bis zum ersten Release existieren keine unterstützten Produktversionen. Nach Veröffentlichung wird hier eine Supportmatrix gepflegt.

## Schwachstellen melden

Sicherheitslücken dürfen nicht als öffentliches GitHub-Issue gemeldet werden. Bis ein dedizierter Meldekanal eingerichtet ist, ist der Repository-Eigentümer direkt und vertraulich zu kontaktieren.

Eine Meldung sollte enthalten:

- betroffene Version
- reproduzierbare Schritte
- mögliche Auswirkungen
- vorhandener Proof of Concept ohne reale Patientendaten
- vorgeschlagene Abhilfe, falls bekannt

## Bearbeitung

1. Eingang bestätigen
2. Schweregrad und Scope bewerten
3. Reproduktion in isolierter Umgebung
4. Korrektur und Tests
5. koordinierte Veröffentlichung
6. Changelog, Advisory und betroffene Dokumentation aktualisieren
7. Wirksamkeit der Korrektur prüfen

## Sicherheitsgrundsätze

- Keine echten Patientendaten in Issues, Logs, Screenshots oder Testfällen.
- Keine Secrets im Repository.
- Abhängigkeiten und Container werden automatisiert geprüft.
- Kritische Fixes umgehen nicht Review und Tests; sie erhalten ein beschleunigtes Verfahren.
- Sicherheitsrelevante Änderungen werden im Changelog gekennzeichnet.

Siehe außerdem `docs/Security/`.
