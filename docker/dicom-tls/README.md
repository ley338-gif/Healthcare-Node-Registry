# DICOM-TLS-Profile

Dieses Verzeichnis enthält keine Zertifikate im Repository. Für lokale Tests kann je Profilkennung ein Unterordner angelegt werden:

```text
docker/dicom-tls/
└── pacs-produktiv/
    ├── certificate.pem
    ├── private-key.pem
    └── ca.pem
```

In produktiven Installationen sollte `DIAGNOSTIC_TLS_PROFILE_HOST_DIRECTORY` auf ein außerhalb des Repositorys liegendes, nur für den Container lesbares Verzeichnis zeigen. Inhalte dieses Ordners werden durch `.gitignore` ausgeschlossen.

Der private Schlüssel muss unverschlüsselt vorliegen und über Dateisystemrechte geschützt werden. Dadurch wird vermieden, ein Schlüsselpasswort als DCMTK-Prozessargument offenzulegen. Zertifikat und Schlüssel müssen zusammenpassen; das Client-Zertifikat muss aktuell gültig sein. `ca.pem` enthält die vertrauenswürdige ausstellende CA. Diagnose-Runner verlangen gegenseitige Zertifikatsprüfung und fallen bei Fehlern nicht auf Klartext zurück.
