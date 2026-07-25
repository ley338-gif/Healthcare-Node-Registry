# Git Workflow

## Branches

- `main`: geschützt und releasefähig
- `feature/<issue>-<slug>`
- `fix/<issue>-<slug>`
- `docs/<issue>-<slug>`
- `security/<issue>-<slug>`

## Pull Requests

Mindestens ein Review für normale Änderungen. Sicherheits-, Authentisierungs-, Autorisierungs-, Migrations- und Releaseänderungen benötigen einen besonders qualifizierten Review.

## Merge

Squash oder Rebase gemäß späterem ADR; keine unverständlichen Merge-Historien.

## Releases

- Tag `vX.Y.Z`
- signierte/prüfbare Artefakte
- GitHub Release
- Changelog und Release Notes
- SBOM
- Migrations- und Backuphinweise
