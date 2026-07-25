# ADR-0006: Endpoint and DICOM Model

- **Status:** Proposed

## Entscheidung

Endpoints besitzen generische Basisdaten. Protokollspezifische Kernattribute werden in Detailtabellen modelliert. DICOM AE ist ein konkreter Endpoint.

DICOM-Dienste und SCU-/SCP-Rollen werden pro Endpoint und Dienst strukturiert modelliert. Connections referenzieren konkrete Quell- und Zielendpoints.

## Nicht ausreichend

- allgemeines SCU-/SCP-Feld am Asset
- kompletter Protokollbereich in JSONB
- Verbindung nur zwischen Assets
- Gleichsetzung von physischem Gerät, System und DICOM AE

## Folgen

Mehr Tabellen, aber bessere Integrität, Suche und Fachlichkeit.
