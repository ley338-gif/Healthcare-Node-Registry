---
title: DICOM-Diagnosebetrieb
description: Netzwerkfreigaben und sichere Betriebsgrenzen der Diagnosefunktionen.
document_type: Administratorhandbuch
chapter: 5
status: draft
version: 0.2
last_updated: 2026-08-08
---

# DICOM-Diagnosebetrieb

Diagnosen werden vom Anwendungscontainer gegen aktive registrierte Knoten ausgeführt. Stellen Sie DNS-Auflösung und ausschließlich erforderlichen ausgehenden TCP-Zugriff bereit. Rückverbindungen sind für die aktuell implementierten SCU-Tests nicht erforderlich.

Freigaben sollten Zieladresse, Port, Dienst, Calling AE, verantwortliche Stelle und Gültigkeitsdauer benennen. Die gespeicherte TLS-Kennzeichnung aktiviert derzeit keine DICOM-TLS-Verbindung im Runner.

Der für alle Node-Diagnosen vorgeschlagene Calling AE Title stammt aus der Umgebungsvariable `DIAGNOSTIC_CALLING_AE_TITLE` (Standard `NODE_REGISTRY`, siehe `config/diagnostics.php`). Er lässt sich installationsweit umstellen, z. B. auf einen dedizierten Test-AE wie `HNR_TEST`; das Zielsystem muss diesen AE Title für den jeweiligen Dienst freigeben. Bleibt die Variable unverändert, ändert sich das bestehende Verhalten nicht. Die vom Discovery-Modul verwendete Calling-AE-Konfiguration (`DISCOVERY_DEFAULT_CALLING_AE`, Standard `HNR_DISCOVERY`) ist davon unabhängig.

C-STORE sendet ein synthetisches Secondary-Capture-Objekt. Klären Sie vor der Freigabe, ob es gespeichert oder weitergeleitet wird und wie es im Zielsystem bereinigt wird. Patientendaten dürfen nicht als Testgrundlage verwendet werden.

Timeouts, Befehlsausgaben und Verlauf sind begrenzt. Eine zentrale CIDR-Allowlist und installationsweite Parallelitätssteuerung sind derzeit nicht verfügbar; Egress-Kontrolle bleibt daher wesentlich. Siehe [Diagnose-Workspace](../Healthcare/DiagnosticTestWorkspace.md).
