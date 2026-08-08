# Netzwerkinterfaces von Systemen

Ein Registry-System kann mehrere Netzwerkinterfaces besitzen. Damit lassen sich Cluster-VIPs, einzelne Clusterknoten, Management-Netze und weitere NICs getrennt dokumentieren.

## Datenmodell

`system_network_interfaces` gehört über `system_id` zu genau einem System und enthält:

- eindeutige Bezeichnung innerhalb des Systems (`interface_label`)
- optional Hostname, FQDN und IP-Adresse; mindestens einer dieser Werte ist erforderlich
- Kennzeichnung des primären Interfaces (`is_primary`)

Die Datenbank erzwingt höchstens ein primäres Interface je System. Solange mindestens ein Interface existiert, stellt die Anwendung außerdem sicher, dass genau eines davon primär ist.

## Abwärtskompatibilität

Die bisherigen Felder `systems.hostname`, `systems.fqdn` und `systems.ip_address` bleiben vorerst erhalten, sind aber als veraltete Spiegelwerte markiert. Sie enthalten immer die Werte des primären Interfaces. Dadurch funktionieren bestehende Suche, CSV-Import, Discovery, Exporte und Integrationen während der Übergangsphase weiter.

Beim Einspielen der Migration werden vorhandene Hostdaten automatisch als `Primärschnittstelle` übernommen. Neue Systeme, die über bestehende Import- oder Erfassungswege mit Hostdaten angelegt werden, erhalten ebenfalls automatisch ein primäres Interface.

## Bedienung

Die Pflege erfolgt im System-Workspace auf dem Reiter **Netzwerk**. Berechtigte Registry-Administratoren können Interfaces anlegen, bearbeiten, als primär kennzeichnen und löschen. Wird das primäre Interface gelöscht, wird ein vorhandenes anderes Interface zum neuen Primärinterface; andernfalls werden die alten Spiegelwerte geleert.

## Suche und Discovery

Die globale Suche, die Systemübersicht und die Discovery-Duplikaterkennung berücksichtigen primäre und sekundäre Interfaces. Berechtigungen werden über die bestehende System-Update-Policy geprüft; es gibt keinen separaten Berechtigungsmechanismus.
