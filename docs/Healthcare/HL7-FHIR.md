# HL7 and FHIR Guidance

## HL7 v2

Zu dokumentieren sind, soweit relevant:

- sendendes und empfangendes System
- Richtung
- Host, Port und Transport
- TLS ja/nein
- Nachrichtenarten und Trigger Events
- Zeichensatz
- ACK-Verhalten
- fachlicher Zweck
- Verantwortlichkeit
- Status und Quelle der Dokumentation

Die Anwendung ist im MVP keine Interface Engine.

## FHIR

Zu dokumentieren sind:

- Base URL
- FHIR-Version
- Authentisierungsverfahren
- TLS/Zertifikatsinformation
- relevante Ressourcen oder Profile
- Capability Statement als Referenz
- Zweck und Verantwortlichkeit

Tokens oder Client Secrets werden nicht als normale Registry-Felder gespeichert. Secret-Management ist getrennt zu behandeln.
