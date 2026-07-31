import type { DocumentationSection } from './DocumentationPanel.vue';

export const organizationDocumentationSections: DocumentationSection[] = [
    {
        key: 'description',
        title: 'Beschreibung',
        description: 'Ergänzende organisatorische und betriebliche Beschreibung.',
        fields: [
            { key: 'purpose', label: 'Zweck und Aufgaben', type: 'textarea', required: true },
            { key: 'operational_context', label: 'Betrieblicher Kontext', type: 'textarea', required: true },
        ],
    },
    {
        key: 'it_responsibility',
        title: 'IT-Verantwortung',
        description: 'Zentrale technische Zuständigkeiten.',
        fields: [
            { key: 'it_owner', label: 'IT-Verantwortlicher', required: true },
            { key: 'deputy', label: 'Vertretung' },
            { key: 'contact_details', label: 'Kontaktdaten', type: 'textarea' },
        ],
    },
    {
        key: 'medical_engineering',
        title: 'Medizintechnik',
        description: 'Verantwortung und Kontakt zur Medizintechnik.',
        fields: [
            { key: 'owner', label: 'Verantwortlicher', required: true },
            { key: 'contact_details', label: 'Kontaktdaten', type: 'textarea' },
        ],
    },
    {
        key: 'data_protection',
        title: 'Datenschutz',
        description: 'Datenschutzverantwortung und relevante Hinweise.',
        fields: [
            { key: 'contact', label: 'Datenschutzkontakt', required: true },
            { key: 'notes', label: 'Datenschutzhinweise', type: 'textarea' },
        ],
    },
    {
        key: 'information_security',
        title: 'Informationssicherheit',
        description: 'Informationssicherheitsverantwortung und Vorgaben.',
        fields: [
            { key: 'contact', label: 'Informationssicherheitskontakt', required: true },
            { key: 'requirements', label: 'Besondere Vorgaben', type: 'textarea' },
        ],
    },
    {
        key: 'central_contacts',
        title: 'Zentrale Ansprechpartner',
        description: 'Fachliche, technische und administrative Kontakte.',
        fields: [{ key: 'contacts', label: 'Ansprechpartner', type: 'textarea', required: true }],
    },
    {
        key: 'support_escalation',
        title: 'Support- und Eskalationswege',
        description: 'Supportkanäle und verbindlicher Eskalationsweg.',
        fields: [
            { key: 'support_path', label: 'Supportweg', type: 'textarea', required: true },
            { key: 'escalation_path', label: 'Eskalationsweg', type: 'textarea', required: true },
        ],
    },
    {
        key: 'special_characteristics',
        title: 'Organisatorische Besonderheiten',
        description: 'Besondere Regeln, Abhängigkeiten und Rahmenbedingungen.',
        fields: [{ key: 'notes', label: 'Besonderheiten', type: 'textarea' }],
    },
    {
        key: 'links_references',
        title: 'Links und Referenzen',
        description: 'Wikis, Richtlinien und weitere zentrale Quellen.',
        fields: [{ key: 'links', label: 'Links und Referenzen', type: 'textarea' }],
    },
];

export const siteDocumentationSections: DocumentationSection[] = [
    {
        key: 'description',
        title: 'Beschreibung',
        description: 'Ergänzende Beschreibung des Standorts.',
        fields: [{ key: 'purpose', label: 'Aufgabe und Nutzung', type: 'textarea', required: true }],
    },
    {
        key: 'local_information',
        title: 'Adresse und lokale Informationen',
        description: 'Zusätzliche Hinweise zu Erreichbarkeit und Gelände.',
        fields: [
            { key: 'directions', label: 'Anfahrt und Zugang', type: 'textarea' },
            { key: 'local_notes', label: 'Lokale Informationen', type: 'textarea' },
        ],
    },
    {
        key: 'local_it',
        title: 'Lokale IT-Ansprechpartner',
        description: 'Technische Zuständigkeiten vor Ort.',
        fields: [{ key: 'contacts', label: 'Ansprechpartner', type: 'textarea', required: true }],
    },
    {
        key: 'medical_engineering',
        title: 'Medizintechnik',
        description: 'Lokale medizintechnische Zuständigkeiten.',
        fields: [{ key: 'contacts', label: 'Ansprechpartner', type: 'textarea', required: true }],
    },
    {
        key: 'network',
        title: 'Netzwerkbesonderheiten',
        description: 'Standortspezifische Netzwerk- und Infrastrukturhinweise.',
        fields: [{ key: 'notes', label: 'Netzwerkbesonderheiten', type: 'textarea', required: true }],
    },
    {
        key: 'maintenance',
        title: 'Lokale Wartungsfenster',
        description: 'Regelmäßige und besondere Wartungszeiten.',
        fields: [{ key: 'maintenance_windows', label: 'Wartungsfenster', type: 'textarea', required: true }],
    },
    {
        key: 'operating_hours',
        title: 'Öffnungs- und Betriebszeiten',
        description: 'Betriebszeiten und Einschränkungen.',
        fields: [
            { key: 'hours', label: 'Betriebszeiten', required: true },
            { key: 'exceptions', label: 'Ausnahmen', type: 'textarea' },
        ],
    },
    {
        key: 'emergency_contacts',
        title: 'Notfallkontakte',
        description: 'Kontakte für technische und organisatorische Notfälle.',
        fields: [{ key: 'contacts', label: 'Notfallkontakte', type: 'textarea', required: true }],
    },
    {
        key: 'special_characteristics',
        title: 'Lokale Besonderheiten',
        description: 'Standortspezifische Regeln und Einschränkungen.',
        fields: [{ key: 'notes', label: 'Besonderheiten', type: 'textarea' }],
    },
    {
        key: 'links_references',
        title: 'Links und Referenzen',
        description: 'Lokale Wikis, Pläne und weitere Quellen.',
        fields: [{ key: 'links', label: 'Links und Referenzen', type: 'textarea' }],
    },
];

export const departmentDocumentationSections: DocumentationSection[] = [
    {
        key: 'description',
        title: 'Beschreibung',
        description: 'Ergänzende Beschreibung der Abteilung.',
        fields: [{ key: 'purpose', label: 'Aufgabe und Leistungsumfang', type: 'textarea', required: true }],
    },
    {
        key: 'specialty',
        title: 'Fachbereich',
        description: 'Fachliche Ausrichtung und Schwerpunkte.',
        fields: [{ key: 'focus', label: 'Fachliche Schwerpunkte', type: 'textarea', required: true }],
    },
    {
        key: 'business_contacts',
        title: 'Fachliche Ansprechpartner',
        description: 'Fachliche Verantwortung und Vertretung.',
        fields: [{ key: 'contacts', label: 'Ansprechpartner', type: 'textarea', required: true }],
    },
    {
        key: 'technical_contacts',
        title: 'Technische Ansprechpartner',
        description: 'Technische Zuständigkeiten für die Abteilung.',
        fields: [{ key: 'contacts', label: 'Ansprechpartner', type: 'textarea', required: true }],
    },
    {
        key: 'modalities_systems',
        title: 'Modalitäten und Systeme',
        description: 'Betriebliche Ergänzungen zur verknüpften Systemliste.',
        fields: [{ key: 'notes', label: 'Hinweise zu Modalitäten und Systemen', type: 'textarea' }],
    },
    {
        key: 'operating_hours',
        title: 'Betriebszeiten',
        description: 'Regelbetrieb, Bereitschaft und Ausnahmen.',
        fields: [
            { key: 'hours', label: 'Betriebszeiten', required: true },
            { key: 'on_call', label: 'Bereitschaft', type: 'textarea' },
        ],
    },
    {
        key: 'local_workflows',
        title: 'Lokale Abläufe',
        description: 'Wichtige fachliche und technische Abläufe.',
        fields: [{ key: 'workflows', label: 'Abläufe', type: 'textarea', required: true }],
    },
    {
        key: 'special_characteristics',
        title: 'Besonderheiten',
        description: 'Abteilungsspezifische Regeln und Einschränkungen.',
        fields: [{ key: 'notes', label: 'Besonderheiten', type: 'textarea' }],
    },
    {
        key: 'escalation',
        title: 'Eskalationsweg',
        description: 'Fachlicher und technischer Eskalationsprozess.',
        fields: [{ key: 'path', label: 'Eskalationsweg', type: 'textarea', required: true }],
    },
    {
        key: 'links_references',
        title: 'Links und Referenzen',
        description: 'Arbeitsanweisungen, Wikis und weitere Quellen.',
        fields: [{ key: 'links', label: 'Links und Referenzen', type: 'textarea' }],
    },
];
