# Anwendung dieses Foundation-Overlays

Dieses ZIP ist für das Repository `ley338-gif/Healthcare-Node-Registry` bestimmt.

## Zweck

Das Overlay bereinigt die Foundation-Dokumentation vor der ersten Laravel-Initialisierung. Es enthält keine ausführbare Anwendung und verändert keine Referenzbilder.

## Empfohlener Git-Ablauf

```bash
git checkout main
git pull
git checkout -b docs/sprint-0-foundation-decisions
```

Den Inhalt dieses Ordners anschließend in das Stammverzeichnis des Repositorys kopieren und vorhandene Textdateien ersetzen.

```bash
git status
git diff --check
git add README.md ROADMAP.md CHANGELOG.md KNOWN_ISSUES.md specification docs .github
git commit -m "docs: finalize sprint 0 foundation decisions"
git push -u origin docs/sprint-0-foundation-decisions
```

Danach einen Draft Pull Request gegen `main` eröffnen.

## Nicht überschreiben oder löschen

Die vorhandenen Dateien bleiben bestehen:

- `specification/network-architecture-reference.png`
- `specification/ui-reference.png`
- sonstige Repository-Dateien, die in diesem Overlay nicht enthalten sind

## Erwartetes Ergebnis

Nach Übernahme sind die wichtigsten Foundation-Entscheidungen dokumentiert. Vor der Laravel-Initialisierung müssen nur noch die als `Proposed` markierten ADRs geprüft und formell akzeptiert werden.
