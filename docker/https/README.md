# HTTPS-Zertifikate

Dieses Verzeichnis ist der lokale Standard-Mount für das optionale HTTPS-Deployment. Vor dem Start müssen genau diese beiden, vom Betreiber verwalteten Dateien vorhanden sein:

- `tls.crt`: Serverzertifikat einschließlich erforderlicher Zwischenzertifikate im PEM-Format
- `tls.key`: zugehöriger unverschlüsselter privater Schlüssel im PEM-Format

Zertifikate und private Schlüssel sind durch `.gitignore` ausgeschlossen. Produktive Installationen sollten mit `HTTPS_CERTIFICATE_HOST_DIRECTORY` stattdessen ein außerhalb des Repositorys liegendes, nur für den Container lesbares Verzeichnis einbinden. Fehlen die Dateien oder passen Zertifikat und Schlüssel nicht zusammen, startet Nginx nicht.
