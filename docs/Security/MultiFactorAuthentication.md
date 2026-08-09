# Mehrfaktor-Authentisierung

## Geltungsbereich

Die HNR erzwingt zeitbasierte Einmalpasswörter (TOTP) für lokale Konten mit privilegierten Berechtigungen. Die Pflicht hängt nicht allein an einem Rollennamen: Sie gilt für den geschützten `system-administrator` sowie für Rollen mit schreibenden Administrations-, Registry-, Dokument-, Discovery- oder aktiven Diagnoseberechtigungen. Die verbindliche Liste steht in `config/mfa.php`.

Reine Lesekonten ohne diese Berechtigungen benötigen aktuell keinen zweiten Faktor. Externe Identitätsanbieter, WebAuthn/Passkeys, SMS und E-Mail-Codes sind nicht implementiert.

## Erste Einrichtung

Nach korrektem Passwort-Login wird ein privilegiertes Konto ohne bestätigte MFA ausschließlich zur Seite `/mfa/setup` zugelassen. Dort wird ein lokal erzeugter Einrichtungsschlüssel über einen `otpauth://`-Link oder zur manuellen Eingabe bereitgestellt. Der Benutzer bestätigt einen sechsstelligen Code seiner TOTP-kompatiblen Authenticator-App.

Nach der Bestätigung zeigt die Anwendung einmalig acht Wiederherstellungscodes. Jeder Code ist nur einmal verwendbar und muss getrennt vom Authenticator-Gerät sicher aufbewahrt werden. In der Datenbank liegt der TOTP-Schlüssel mit `APP_KEY` verschlüsselt; Wiederherstellungscodes werden normalisiert, mit einem aus `APP_KEY` abgeleiteten HMAC gehasht und zusätzlich im verschlüsselten Feld gespeichert.

## Anmeldung und Schutzmaßnahmen

Bei aktivierter MFA erzeugt ein korrektes Passwort noch keine authentifizierte Sitzung. Es entsteht eine auf fünf Minuten begrenzte Challenge; erst nach gültigem TOTP- oder Wiederherstellungscode wird die Sitzung angemeldet und optional ein Remember-Cookie gesetzt. Passwort- und MFA-Versuche sind getrennt gedrosselt. Ein verwendeter TOTP-Zeitschritt kann nicht erneut genutzt werden; es werden höchstens der aktuelle und die beiden direkt benachbarten 30-Sekunden-Schritte akzeptiert.

Erfolgreiche MFA-Verifikation, Verwendung eines Wiederherstellungscodes, Einrichtung und Reset werden im Audit protokolliert. Geheimnisse und Klartext-Wiederherstellungscodes erscheinen nicht im Audit.

## Reset und Break Glass

Ein berechtigter Administrator kann MFA in `Einstellungen > Benutzerverwaltung` zurücksetzen. Dabei werden TOTP-Schlüssel, Recovery-Code-Hashes und Remember-Token entfernt sowie sämtliche Datenbanksitzungen des Kontos widerrufen. Besitzt das Konto weiterhin privilegierte Rechte, muss es MFA beim nächsten Passwort-Login neu einrichten.

Wenn kein anderer Administrator verfügbar ist, kann ein Betreiber mit kontrolliertem Konsolenzugriff den auditierten Break-Glass-Befehl verwenden:

```bash
docker compose exec app php artisan registry:reset-mfa admin@example.org
```

Für einen nicht interaktiven, organisatorisch freigegebenen Notfalllauf steht `--force` zur Verfügung. Shell-Zugriff, Ausführung, Anlass und neue Einrichtung müssen nach lokalem Notfallverfahren dokumentiert werden.

## Betriebsanforderungen

- HTTPS und `SESSION_SECURE_COOKIE=true` für alle nicht isolierten Installationen erzwingen.
- Zeit auf Host, Containern und Authenticator-Geräten per NTP synchron halten.
- `APP_KEY` und Datenbank gemeinsam sichern. Ohne den ursprünglichen Schlüssel können vorhandene TOTP-Secrets nicht entschlüsselt werden.
- `APP_KEY` nicht unkoordiniert rotieren; eine Rotation benötigt einen Migrations- und Wiederanlaufplan für verschlüsselte Daten.
- Recovery-Codes nicht in Tickets, Screenshots, Logs oder Registry-Dokumenten ablegen.
- MFA-Reset-Ereignisse und verbleibende privilegierte Konten regelmäßig im Audit prüfen.
