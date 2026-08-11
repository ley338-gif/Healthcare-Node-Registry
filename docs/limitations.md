---
title: Bekannte Einschränkungen
description: Explizit dokumentierte Grenzen des DICOM Discovery & Topology MVP.
document_type: Einschränkungen
chapter: Übersicht
status: draft
version: 0.1
last_updated: 2026-08-08
---

# Bekannte Einschränkungen

Diese Seite unterscheidet ausdrücklich zwischen **im MVP implementiert**, **technisch vorbereitet**, **geplant** und **bewusst nicht implementiert**. Siehe auch `docs/roadmap.md` für mögliche Version-2-Funktionen.

## Grundsätzliche, dauerhafte Einschränkungen (bewusst nicht implementiert)

Diese gelten unabhängig von künftigen Versionen, solange die Anwendung kein Medizinprodukt ist:

- **AE-Titel können nicht zuverlässig automatisch ausgelesen werden.** Discovery testet ausschließlich konfigurierte AE-Titel-Kandidaten (manuell, importiert, aus der Registry, vom Hostnamen abgeleitet, oder wenige dokumentierte Standardwerte). Es gibt keinen DICOM-Mechanismus, um einen unbekannten Called-AE-Titel zuverlässig zu erraten.
- **Ein offener Port beweist keinen DICOM-Dienst.** Ports werden nur als "DICOM-Kandidat" markiert; erst ein erfolgreicher C-ECHO liefert einen (weiterhin heuristischen) Hinweis.
- **Ein erfolgreicher C-ECHO beweist keine produktive Verbindung zwischen zwei Systemen.** Er beweist ausschließlich die Erreichbarkeit des getesteten Endpunkts zum Testzeitpunkt. Topologie-Verbindungen entstehen daher nie automatisch, sondern ausschließlich durch eine bewusste Benutzeraktion.
- **Es werden keine Patientendaten verarbeitet.** Kein Bildabruf, kein C-STORE mit echten Bilddaten, kein Worklist-Abruf, keine Study-/Series-Metadaten, keine Patientennamen oder Accession Numbers.
- **Es dürfen ausschließlich Netzbereiche gescannt werden, für die eine ausdrückliche Berechtigung vorliegt.** Die Anwendung erzwingt dies technisch über eine Administrator-gepflegte Freigabeliste (`discovery_allowed_networks`, Standard: nur RFC1918), kann eine tatsächliche organisatorische Berechtigung aber nicht verifizieren - das bleibt Aufgabe des Betreibers.

## Technische MVP-Vereinfachungen

- **Ein Zielbereich pro Discovery-Lauf.** Mehrere getrennte Bereiche in einem Lauf sind nicht vorgesehen (siehe ADR-0011).
- **Parallelität ist auf einen Batch begrenzt**, keine Multi-Prozess- oder Multi-Worker-Architektur. Für die im MVP erwartete Nutzung (einzelne, gezielte Läufe) ausreichend, aber nicht für sehr große, zeitkritische Scans optimiert.
- **ICMP-Ping erfordert `CAP_NET_RAW` im Container.** Fehlt diese Capability, gilt ein Host trotzdem als erreichbar, sobald ein konfigurierter Port antwortet - eine reine ICMP-Aussage ("Host antwortet nicht auf Ping, aber auf keinem Port") wird in diesem Fall nicht separat ausgewiesen.
- **Keine Bulk-Auswahl in der Review-Queue.** Bestätigen/Ignorieren/Übernehmen erfolgt je Fund einzeln, keine Mehrfachauswahl mit Sammelaktion.
- **Kein separates Rollen-Freigabe-Vier-Augen-Prinzip.** Die Übernahme in die Registry erfordert eine einzelne Person mit `discovery.manage` und `registry.manage`.

## Nicht implementiert (siehe `docs/roadmap.md` für mögliche Version 2)

Import mehrerer Discovery-Zielbereiche oder Läufe, Worklist-/Query-Retrieve-/Storage-Commitment-/MPPS-Tests im Discovery-Kontext, DICOM-TLS, SNMP, passive Sensorik, Herstellerprofile, Conformance-Statement-Import, externes Monitoring/Alarmierung, Vier-Augen-Freigabe-Workflow, versionierte Topologie und automatische Änderungsberichte. Ein CSV-/XLSX-Import für AE-Titel-Kandidaten, gefilterte CSV-/JSON-/PDF-Exporte, ein manueller Vergleich abgeschlossener Scanstände sowie deduplizierte In-App-Hinweise für neue oder verschwundene Hosts in erfolgreichen Zeitplanläufen sind vorhanden; E-Mail, Webhooks und externe Alarmkanäle sind nicht implementiert.
