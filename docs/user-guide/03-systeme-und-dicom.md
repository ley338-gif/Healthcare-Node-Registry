---
title: Systeme und DICOM
description: Systeme, DICOM-Knoten und Kommunikationsbeziehungen pflegen.
document_type: Benutzerhandbuch
chapter: 3
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Systeme und DICOM

## Systeme pflegen

Öffnen Sie **Systeme**, wählen Sie ein System oder legen Sie ein neues an. Pflegen Sie Namen, Typ, Status, Herstellerangaben und Organisationszuordnung nur auf Basis einer belastbaren Quelle. Der System-Workspace bündelt Stammdaten, Dokumentation, DICOM-Knoten und Historie.

## DICOM-Knoten pflegen

Ein DICOM-Knoten beschreibt eine Application Entity mit AE Title, Rolle, Status, Diensten und – soweit vorhanden – einem Netzwerkendpunkt. Prüfen Sie insbesondere:

- AE Title mit maximal 16 Zeichen;
- bei `SCP` oder `SCU & SCP` den im Anwendungscontainer auflösbaren Host beziehungsweise die korrekte IP-Adresse und den tatsächlichen DICOM-Port;
- bei einer reinen `SCU` nur tatsächlich bekannte Endpunktdaten; AE Title allein oder AE Title mit IP-Adresse ist zulässig;
- SCU-/SCP-Rolle je fachlichem Dienst;
- aktiven Status nur für verwendbare Ziele. Technische Diagnosen benötigen immer Host und Port.

## Kommunikationsbeziehungen

Eine Verbindung ordnet Quellknoten, Zielknoten und DICOM-Dienst zu. Sie dokumentiert einen erwarteten Kommunikationsweg, garantiert aber weder Erreichbarkeit noch fachliche Freigabe.

Archivieren Sie veraltete Knoten oder Beziehungen. Nutzen Sie Diagnosefunktionen erst nach Freigabe des Zielsystems. Fachliche Hintergründe stehen in der [DICOM-Referenz](../dicom-reference/README.md).
