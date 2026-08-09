---
title: Identitäten und Berechtigungen
description: Lokale Konten, Rollen und Access Reviews betreiben.
document_type: Administratorhandbuch
chapter: 2
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Identitäten und Berechtigungen

Die HNR verwendet lokale, sessionbasierte Authentisierung, serverseitig durchgesetztes RBAC und verpflichtende TOTP-MFA für privilegierte Konten. Externe Identitätsanbieter sind nicht verfügbar.

Erstellen Sie den initialen Administrator über das vorgesehene Konsolenkommando und verwenden Sie anschließend **Einstellungen > Benutzerverwaltung**. Vergeben Sie persönliche Konten, minimale Rollen und getrennte Rechte für sensible Dokument-, Export- und Storage-Aktionen.

Ein Access Review soll regelmäßig Benutzerstatus, Rollen, privilegierte Rechte, verwaiste Konten und administrativen Wiederherstellungszugang prüfen. Sperrung ist einer unkontrollierten Löschung vorzuziehen, wenn Auditbezüge erhalten bleiben müssen.

Privilegierte Benutzer richten beim ersten Login eine Authenticator-App ein und sichern die acht einmaligen Wiederherstellungscodes getrennt vom Gerät. Ein Administrator kann MFA nach dokumentierter Identitätsprüfung in der Benutzerverwaltung zurücksetzen. Steht kein zweites Administratorkonto zur Verfügung, beschreibt [Mehrfaktor-Authentisierung](../Security/MultiFactorAuthentication.md) den auditierten Konsolen-Break-Glass-Befehl. Rollen- und Berechtigungsänderungen beenden vorhandene Sitzungen der betroffenen Konten.

Grundlagen: [Authentisierung](../Security/Authentication.md), [Zugriffskontrolle](../Security/AccessControl.md) und [Benutzerverwaltung](../Features/UserManagement.md).
