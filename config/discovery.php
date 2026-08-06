<?php

return [
    /*
     * Maximale Anzahl an IPv4-Adressen, die ein einzelner Discovery-Lauf
     * umfassen darf. Schützt vor versehentlich zu großen Scanbereichen.
     */
    'max_range_size' => (int) env('DISCOVERY_MAX_RANGE_SIZE', 1024),

    /*
     * Ab dieser Anzahl an Adressen wird im Wizard eine Warnung angezeigt,
     * der Scan bleibt aber innerhalb von max_range_size möglich.
     */
    'large_range_warning_threshold' => (int) env('DISCOVERY_LARGE_RANGE_WARNING_THRESHOLD', 256),

    /*
     * Standard Calling AE Title, mit dem Discovery-Scans DICOM-Endpunkte
     * kontaktieren (Abschnitt 8 des Lastenhefts).
     */
    'default_calling_ae_title' => (string) env('DISCOVERY_DEFAULT_CALLING_AE', 'HNR_DISCOVERY'),

    /*
     * Obergrenze für gleichzeitig verarbeitete Hosts pro Lauf
     * ("maximale parallele Hosts" im Wizard).
     */
    'max_parallel_hosts' => (int) env('DISCOVERY_MAX_PARALLEL_HOSTS', 16),

    /*
     * Harte Obergrenze für getestete AE-Titel-Kandidaten je Host und Port -
     * verhindert unbegrenztes Brute-Forcing (Abschnitt 8/9).
     */
    'max_ae_attempts_per_port' => (int) env('DISCOVERY_MAX_AE_ATTEMPTS_PER_PORT', 5),

    /*
     * Zeitlimits in Sekunden für einzelne technische Prüfungen.
     */
    'timeouts' => [
        'ping_seconds' => (int) env('DISCOVERY_PING_TIMEOUT', 2),
        'tcp_connect_seconds' => (int) env('DISCOVERY_TCP_TIMEOUT', 2),
        'dicom_echo_seconds' => (int) env('DISCOVERY_DICOM_ECHO_TIMEOUT', 5),
    ],

    /*
     * Standardmäßig vorgeschlagene DICOM-Ports (Abschnitt 9).
     */
    'default_dicom_ports' => [104, 11112, 11113, 2761, 2762, 4242, 5678, 7104, 8000, 8042, 8104],

    /*
     * Optionale Infrastruktur-Ports, die im Wizard zusätzlich angeboten
     * werden, aber standardmäßig deaktiviert sind.
     */
    'optional_infrastructure_ports' => [80, 443, 389, 636, 1433, 1521, 3306, 5432],

    /*
     * Anzahl der Portprüfungen, die parallel (nicht-blockierend) je Host
     * gestartet werden.
     */
    'port_scan_batch_size' => (int) env('DISCOVERY_PORT_SCAN_BATCH_SIZE', 12),

    /*
     * Hartes Zeitlimit für den gesamten Scan-Job (Sekunden). Schützt den
     * Worker davor, dass ein einzelner Lauf unbegrenzt lange blockiert.
     */
    'job_timeout_seconds' => (int) env('DISCOVERY_JOB_TIMEOUT', 3600),

    /*
     * Bekannte Infrastruktur-Ports zur Klassifizierungs-Heuristik
     * (Abschnitt 12: "Web-Port vorhanden" / "Datenbank-Port vorhanden").
     */
    'web_ports' => [80, 443],
    'database_ports' => [389, 636, 1433, 1521, 3306, 5432],
];
