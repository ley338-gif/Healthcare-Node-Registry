---
title: Produktvision
description: Vision, Zielsetzung, Zielgruppen und Abgrenzung der Healthcare Node Registry.
document_type: Produktbuch
chapter: 1
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Produktvision

## Zweck des Kapitels

Dieses Kapitel beschreibt die fachliche Vision, die Zielsetzung und die grundlegenden Produktprinzipien der Healthcare Node Registry (HNR). Es grenzt den aktuellen Funktionsumfang von geplanten Erweiterungen und der langfristigen Produktvision ab. Das Kapitel ist die fachliche Grundlage für nachfolgende Produkt-, Benutzer-, Administrations- und Architekturkapitel.

## Produktüberblick

Die Healthcare Node Registry ist ein proprietäres, kommerzielles On-Premises-Produkt zur Dokumentation, Verwaltung, Analyse und technischen Überprüfung medizinischer Kommunikationsinfrastrukturen. Sie bildet Organisationen, Standorte, Abteilungen, Systeme, DICOM-Knoten und ihre Kommunikationsbeziehungen in einem gemeinsamen fachlichen Zusammenhang ab.

Der aktuelle Produktstand unterstützt die strukturierte Registry, DICOM-bezogene Topologie und Diagnose, versionierte Dokumente, Änderungshistorien, Audit sowie eine lokale Benutzer-, Rollen- und Berechtigungsverwaltung. Der Funktionsumfang wird versionsbezogen dokumentiert; ein Eintrag in der langfristigen Vision bedeutet nicht, dass die betreffende Funktion bereits verfügbar ist.

## Ausgangssituation

Medizinische Kommunikationsinfrastrukturen bestehen typischerweise aus PACS, RIS, KIS, VNA, Modalitäten, Befundungsarbeitsplätzen, Schnittstellen und weiteren technischen Komponenten. Informationen zu diesen Systemen liegen häufig verteilt in Tabellen, Wiki-Seiten, Tickets, Herstellerunterlagen und individuellem Erfahrungswissen vor.

DICOM-spezifische Angaben wie AE-Titel, Netzwerkadresse, Port, SCU-/SCP-Rolle und unterstützte Dienste werden dabei oft getrennt von Organisationszuordnung, Systemverantwortung, technischer Dokumentation und Änderungshistorie gepflegt. Die Aktualität und Nachvollziehbarkeit dieser Informationen ist dadurch schwer sicherzustellen.

## Problemstellung

Verteilte oder uneinheitliche Dokumentation erschwert den sicheren Betrieb medizinischer Kommunikationsinfrastrukturen. Typische Folgen sind:

- unklare Zuordnung von Systemen und DICOM-Knoten zur Organisationsstruktur;
- veraltete oder widersprüchliche Angaben zu AE-Titeln, Hosts und Ports;
- fehlende Übersicht über Kommunikationsbeziehungen und DICOM-Dienste;
- zeitaufwendige Fehleranalyse bei Netzwerk-, Modality-Worklist- oder Query/Retrieve-Problemen;
- fehlende Nachvollziehbarkeit von Änderungen;
- Dokumente ohne eindeutigen fachlichen Kontext oder Versionsbezug;
- Abhängigkeit von Einzelpersonen und lokalem Erfahrungswissen.

## Produktvision

Die Healthcare Node Registry soll die verlässliche, fachlich verständliche und revisionsorientierte Referenz für medizinische Kommunikationsknoten und ihre technischen Beziehungen innerhalb einer Organisation sein. Sie verbindet strukturierte Stammdaten, DICOM-Fachlichkeit, technische Diagnose, Dokumentation und nachvollziehbare Änderungen in einer konsistenten Arbeitsumgebung.

Die HNR unterstützt Fach- und Betriebsteams dabei, den dokumentierten Sollzustand ihrer Infrastruktur zu verstehen, Kommunikationswege gezielt zu prüfen und technische Informationen dauerhaft wartbar zu halten.

## Mission

Die Mission der HNR ist es, verstreutes Wissen über Healthcare-IT-Infrastrukturen in ein kontrolliertes, nachvollziehbares und für den technischen Betrieb nutzbares Datenmodell zu überführen. Das Produkt soll die tägliche Administration unterstützen, ohne selbst zum führenden klinischen System oder zu einer allgemeinen Enterprise-CMDB zu werden.

## Zielgruppen

### PACS- und DICOM-Administratoren

Sie benötigen belastbare Angaben zu DICOM-Knoten, AE-Titeln, Ports, Rollen, Diensten, Kommunikationsbeziehungen und technischen Prüfergebnissen.

### Healthcare-IT und Systemadministration

Sie verwalten Systeme, Standorte, technische Zuständigkeiten, Netzwerkbezüge und betriebliche Dokumentation über organisatorische Grenzen hinweg.

### Applikationsbetreuung für RIS, KIS und VNA

Sie benötigen nachvollziehbare Abhängigkeiten zwischen Anwendungen, DICOM-Diensten und organisatorischen Einheiten.

### IT-Betrieb und Support

Sie verwenden die Registry zur Orientierung, Eingrenzung technischer Störungen und Vorbereitung kontrollierter Prüfungen.

### Informationssicherheit, Audit und Qualitätsmanagement

Sie benötigen nachvollziehbare Berechtigungen, Änderungshistorien, Audit-Ereignisse und versionierbare technische Dokumentation.

### Verantwortliche für Architektur und Integration

Sie bewerten Kommunikationsbeziehungen, Systemgrenzen und zukünftige Integrationsanforderungen auf Basis eines konsistenten Bestandsmodells.

## Zentrale Anwendungsfälle

Zum aktuellen Funktionsumfang gehören:

- Abbildung von Organisationen, Standorten und Abteilungen;
- Verwaltung technischer Systeme und ihrer Zuordnung zur Organisationsstruktur;
- Verwaltung von DICOM-Knoten und Kommunikationsbeziehungen;
- Darstellung der DICOM-Topologie;
- kontrollierte Netzwerk- und DICOM-Diagnosen, darunter C-ECHO, Modality Worklist, PACS Query, Capability-Prüfung und kontrollierter C-STORE;
- Verwaltung von Testprofilen und nachvollziehbaren Testergebnissen;
- strukturierte Dokumentation und versionierte Dokumentablage im Registry-Kontext;
- zentrale Suche über freigegebene Registry-Bereiche;
- lokale Benutzer-, Rollen- und Berechtigungsverwaltung;
- Änderungshistorie und zentraler Audit-Arbeitsbereich.

Geplante Anwendungsfälle werden erst nach fachlicher Priorisierung und technischer Freigabe Bestandteil des Produkts. Dazu zählen unter anderem weitergehende Referenzdatenverwaltung, zusätzliche Sicherheits- und Betriebsfunktionen sowie ausgewählte Integrationen. Langfristig können standardisierte Schnittstellen und externe Identitätsanbieter ergänzt werden; sie sind nicht Bestandteil des hier beschriebenen aktuellen Funktionsumfangs.

## Produktprinzipien

### Fachliche Eindeutigkeit

Objekte, Beziehungen, Statuswerte und Verantwortlichkeiten sollen eindeutig benannt und in ihrem fachlichen Kontext dargestellt werden. DICOM-Begriffe werden entsprechend ihrer etablierten Bedeutung verwendet.

### Nachvollziehbarkeit

Relevante Änderungen und technische Prüfungen sollen einem Zeitpunkt, einem Objekt und – soweit vorhanden – einem Benutzer zugeordnet werden können. Historische Informationen dürfen nicht durch reine Überschreibung unkenntlich werden.

### Bestehende Architektur weiterentwickeln

Neue Funktionen sollen vorhandene Modelle, Berechtigungen, Services und UI-Muster verwenden. Parallelstrukturen für denselben fachlichen Zweck werden vermieden.

### Kontrollierte Komplexität

Die HNR bildet die für Healthcare-IT und DICOM relevanten Zusammenhänge ab. Allgemeine Infrastrukturverwaltung wird nur dort vertieft, wo sie diesen Produktzweck unterstützt.

## Proprietäres und kommerzielles Produktmodell

Die Healthcare Node Registry ist proprietäre kommerzielle Software. Der Quellcode ist nicht öffentlich und das Produkt ist weder Open Source noch Open Core. Entwicklung, Qualitätssicherung, Releases und Dokumentationspflege erfolgen in privaten Repositories und kontrollierten Prozessen.

GitHub wird für private Versionsverwaltung, Issues, Projektmanagement, Releases, CI/CD, automatisierte Qualitätsprüfungen, Architecture Decision Records und eine nachvollziehbare Änderungshistorie eingesetzt. Produktdokumentation kann später ganz oder teilweise öffentlich bereitgestellt werden. Daraus entsteht kein Anspruch auf Veröffentlichung des Quellcodes.

## On-Premises-First

Die HNR ist für den Betrieb innerhalb der kontrollierten Infrastruktur einer Organisation ausgelegt. Datenhaltung, Anwendung und technische Diagnose verbleiben grundsätzlich in der On-Premises-Umgebung. Externe Dienste oder Cloud-Komponenten dürfen nicht stillschweigend vorausgesetzt werden.

On-Premises-First bedeutet nicht, dass jede zukünftige Integration ausgeschlossen ist. Externe Anbindungen müssen jedoch ausdrücklich geplant, sicherheitstechnisch bewertet, dokumentiert und durch die betreibende Organisation kontrolliert werden.

## Security by Design

Sicherheitsanforderungen werden als Bestandteil von Architektur und Funktionsdesign behandelt. Dazu gehören insbesondere serverseitige Autorisierung, restriktive Standardentscheidungen, kontrollierte Sitzungen, nachvollziehbare Sicherheitsereignisse und gesonderte Berechtigungen für sensible Aktionen.

Diagnosefunktionen dürfen nicht als allgemeines Netzwerkwerkzeug wirken. Schreibende oder potenziell datenverändernde DICOM-Aktionen benötigen strengere Kontrollen als reine Anzeige- oder Verbindungsprüfungen. Geplante Erweiterungen werden vor ihrer Umsetzung gegen Bedrohungsmodell und Betriebsanforderungen geprüft.

## Privacy by Design

Die HNR dient der technischen Infrastrukturverwaltung und ist nicht für die dauerhafte Speicherung klinischer Patientendaten vorgesehen. Test-, Protokoll- und Exportfunktionen sollen nur die für den technischen Zweck erforderlichen Informationen verarbeiten und sensible Inhalte soweit möglich vermeiden oder bereinigen.

Datensparsamkeit, kontrollierte Zugriffe, lokale Verarbeitung und nachvollziehbare Aufbewahrungsentscheidungen sind verbindliche Leitlinien. Eine technische Möglichkeit zur Verarbeitung von Daten begründet keinen fachlichen oder rechtlichen Verwendungszweck.

## API First

Fachliche Anwendungsfälle sollen über klar definierte, autorisierte und langfristig wartbare Anwendungsgrenzen umgesetzt werden. API First beschreibt dabei eine Architekturleitlinie, nicht die Zusage einer bereits öffentlich verfügbaren Produkt-API.

Der aktuelle Webclient verwendet bestehende serverseitige Anwendungslogik. Eine öffentlich dokumentierte oder für Drittsysteme freigegebene API ist geplant, sobald stabile Verträge, Authentisierung, Versionierung, Berechtigungsmodell und Betriebsanforderungen verbindlich festgelegt sind.

## Abgrenzung zu NetBox und klassischen CMDB-Systemen

NetBox und klassische Configuration Management Database-Systeme verwalten breite Bestände an Netzwerk-, Infrastruktur- und Konfigurationsobjekten. Die Healthcare Node Registry verfolgt einen engeren fachlichen Schwerpunkt: medizinische Kommunikationssysteme, DICOM-Knoten, DICOM-Dienste, technische Prüfungen und den zugehörigen Organisations- und Dokumentationskontext.

Die HNR versucht nicht, den vollständigen Funktionsumfang von NetBox oder einer Enterprise-CMDB nachzubilden. Insbesondere allgemeines IP Address Management, umfassendes Rechenzentrums- und Rack-Management, Beschaffungsprozesse, Softwareverteilung und universelle Discovery gehören nicht automatisch zum Produktumfang.

## Ergänzung statt Ersatz

Die Healthcare Node Registry ist als fachliche Ergänzung zu vorhandenen Inventar-, Netzwerk-, Monitoring-, Ticket- oder CMDB-Systemen gedacht. Eine Organisation kann weiterhin andere Systeme als führende Quelle für allgemeine Infrastrukturinformationen verwenden.

Wo Integrationen langfristig sinnvoll sind, sollen eindeutige Verantwortlichkeiten und Datenführerschaften festgelegt werden. Eine Integration darf nicht zu unkontrollierten Dubletten oder widersprüchlichen Wahrheiten führen.

## Eigenständiges Healthcare- und DICOM-Datenmodell

Die HNR besitzt ein eigenständiges Datenmodell für Organisationsstruktur, technische Systeme, DICOM-Knoten und Kommunikationsbeziehungen. Ein DICOM-Knoten ist nicht lediglich eine IP-Adresse: Er umfasst unter anderem AE-Titel, Host, Port, Rolle, Status und unterstützte Dienste. Kommunikationsbeziehungen verbinden konkrete Quell- und Zielknoten mit einem fachlichen DICOM-Dienst.

Dieses Modell ermöglicht eine healthcare-spezifische Darstellung, Prüfung und Dokumentation, ohne die DICOM-Fachlichkeit in generischen Konfigurationsobjekten zu verlieren.

## Nicht-Ziele

Die Healthcare Node Registry ist nicht:

- ein PACS, RIS, KIS oder VNA;
- ein klinisches Archiv oder eine Patientenakte;
- ein Ersatz für diagnostische Befundung oder klinische Workflows;
- ein universelles Netzwerkmanagement- oder Monitoring-System;
- ein vollständiger Ersatz für NetBox oder eine Enterprise-CMDB;
- ein allgemeiner DICOM-Router oder dauerhaft produktiver DICOM-Datenspeicher;
- ein Werkzeug zur unkontrollierten Erzeugung oder Übertragung von Patientendaten;
- ein Open-Source- oder Open-Core-Projekt.

## Langfristige Produktentwicklung

Die langfristige Produktentwicklung orientiert sich am betrieblichen Mehrwert und an kontrollierbaren fachlichen Risiken. Mögliche Entwicklungsrichtungen sind:

- weitergehende konfigurierbare Referenzdaten und Verwaltungsoberflächen;
- feinere Berechtigungen und organisatorische Geltungsbereiche;
- verbindliche Dokumentfreigaben und Aufbewahrungsregeln;
- zusätzliche abgesicherte DICOM-Diagnosefunktionen;
- standardisierte, versionierte Schnittstellen;
- Anbindung externer Identitätsanbieter wie OIDC oder LDAP/Active Directory;
- verbesserte Auswertungen auf Basis vorhandener Audit- und Ereignisgruppen.

Diese Punkte beschreiben geplante oder langfristig mögliche Entwicklungsrichtungen. Zeitpunkt, Umfang und konkrete Umsetzung sind nicht zugesagt und müssen jeweils separat entschieden, dokumentiert und freigegeben werden.

## Erfolgsbild

Die Produktvision ist erreicht, wenn autorisierte Anwender die für den Betrieb relevanten Healthcare-IT- und DICOM-Zusammenhänge zuverlässig auffinden, verstehen und überprüfen können. Für einen registrierten Kommunikationsweg sollen Organisationskontext, beteiligte Systeme, DICOM-Knoten, technische Parameter, Dokumentation, letzte Prüfungen und wesentliche Änderungen nachvollziehbar sein.

Die HNR reduziert die Abhängigkeit von verstreuten Einzeldokumenten und persönlichem Erfahrungswissen. Sie ersetzt jedoch nicht die organisatorische Verantwortung für Datenpflege, Freigabe, Betrieb und Informationssicherheit.

## Qualitätsmerkmale

Die Healthcare Node Registry orientiert sich an folgenden Qualitätsmerkmalen:

- fachliche Korrektheit und konsistente Terminologie;
- nachvollziehbare Änderungen und Prüfungen;
- serverseitig durchgesetzte Berechtigungen;
- wartbare, erweiterbare Architektur ohne doppelte Fachlogik;
- verständliche, konsistente Benutzerführung;
- lokale Kontrollierbarkeit und sichere Betriebsfähigkeit;
- datensparsame Verarbeitung technischer Informationen;
- testbare Funktionen und automatisierte Qualitätsprüfungen;
- eindeutiger Bezug zwischen Softwareversion und Dokumentation.

## Dokumentationsgrundsatz

Produktdokumentation ist Bestandteil des kontrollierten Produktlebenszyklus. Sie unterscheidet eindeutig zwischen aktuellem Funktionsumfang, geplantem Funktionsumfang und langfristiger Vision. Funktionen werden nicht allein aufgrund einer Planung als verfügbar beschrieben.

Jedes Kapitel wird einzeln erstellt, fachlich und technisch geprüft, versioniert und freigegeben. Änderungen an Funktionen, Architektur, Betrieb oder Sicherheit müssen in den jeweils betroffenen Dokumenten nachvollziehbar werden. Kapitel 1 bildet dafür den Ausgangspunkt; nachfolgende Kapitel werden erst nach gesonderter Freigabe begonnen.
