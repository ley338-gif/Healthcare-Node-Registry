---
title: Installation und Grundkonfiguration
description: Voraussetzungen und kontrollierte Erstinstallation.
document_type: Administratorhandbuch
chapter: 1
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Installation und Grundkonfiguration

## Zweck

Dieses Kapitel beschreibt die kontrollierte On-Premises-Bereitstellung auf Basis des vorhandenen Docker-Compose-Stacks.

## Vor der Installation

- Zielhost, Storage, DNS, Zeitquelle und Backupziel festlegen;
- Netzwerkzugriffe für Benutzer und erforderliche DICOM-Ziele begrenzen;
- TLS-Terminierung und Zertifikatsverantwortung bestimmen;
- produktive Secrets außerhalb der Versionsverwaltung erzeugen;
- Datenbank- und Dokumentvolume in die Sicherung aufnehmen;
- Wartungs- und Wiederherstellungsverantwortung benennen.

Führen Sie Installation und Konfiguration nach [Operations](../Deployment/Operations.md) aus und validieren Sie eine Neuinstallation anhand der [Clean-Install-Prüfung](../Deployment/CleanInstallValidation.md). Beispielwerte sind keine Produktionsvorgaben.

Nach dem Start sind Healthcheck, Anmeldung, initialer Administrator, Schreibzugriff auf private Ablage sowie die Erreichbarkeit erforderlicher Dienste zu prüfen. Der Compose-Stack allein ist noch keine Produktionsfreigabe.
