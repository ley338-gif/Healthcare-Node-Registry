---
title: DICOM-Diagnosebetrieb
description: Netzwerkfreigaben und sichere Betriebsgrenzen der Diagnosefunktionen.
document_type: Administratorhandbuch
chapter: 5
status: draft
version: 0.1
last_updated: 2026-08-02
---

# DICOM-Diagnosebetrieb

Diagnosen werden vom Anwendungscontainer gegen aktive registrierte Knoten ausgeführt. Stellen Sie DNS-Auflösung und ausschließlich erforderlichen ausgehenden TCP-Zugriff bereit. Rückverbindungen sind für die aktuell implementierten SCU-Tests nicht erforderlich.

Freigaben sollten Zieladresse, Port, Dienst, Calling AE, verantwortliche Stelle und Gültigkeitsdauer benennen. Die gespeicherte TLS-Kennzeichnung aktiviert derzeit keine DICOM-TLS-Verbindung im Runner.

C-STORE sendet ein synthetisches Secondary-Capture-Objekt. Klären Sie vor der Freigabe, ob es gespeichert oder weitergeleitet wird und wie es im Zielsystem bereinigt wird. Patientendaten dürfen nicht als Testgrundlage verwendet werden.

Timeouts, Befehlsausgaben und Verlauf sind begrenzt. Eine zentrale CIDR-Allowlist und installationsweite Parallelitätssteuerung sind derzeit nicht verfügbar; Egress-Kontrolle bleibt daher wesentlich. Siehe [Diagnose-Workspace](../Healthcare/DiagnosticTestWorkspace.md).
