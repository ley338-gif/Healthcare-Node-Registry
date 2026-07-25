# Contributing

## Grundsatz

Beiträge müssen dem `AI_ENGINEERING_MANUAL.md` entsprechen.

## Ablauf

1. Issue oder klaren Änderungsauftrag anlegen.
2. Scope und Akzeptanzkriterien festlegen.
3. kurzen Branch erstellen.
4. Code, Tests und Dokumentation gemeinsam ändern.
5. Pull Request mit ausgefüllter Checkliste öffnen.
6. Review und CI abwarten.
7. erst nach Freigabe mergen.

## Qualitätsanforderungen

- keine realen Gesundheitsdaten
- keine Secrets
- keine unkontrollierten Abhängigkeiten
- migrationsfähige Datenbankänderungen
- serverseitige Autorisierung
- Tests für fachliche und sicherheitsrelevante Logik
- Dokumentation und Changelog aktualisiert
- UI folgt der Referenz und dem Designsystem

## Commit-Konvention

Conventional-Commit-Präfixe:

`feat`, `fix`, `docs`, `test`, `refactor`, `security`, `build`, `chore`

Beispiel:

`feat(connections): add endpoint relationship validation`
