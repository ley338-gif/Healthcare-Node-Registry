export type DocumentationField = {
    key: string;
    label: string;
    type?: 'text' | 'textarea' | 'url' | 'boolean';
    required?: boolean;
};

export type DocumentationSection = {
    key: string;
    title: string;
    description: string;
    fields: DocumentationField[];
};

export type RegistryDocumentationItem = {
    public_id: string;
    documentation_type: string;
    section: string;
    title: string;
    content: string | null;
    structured_data: Record<string, unknown>;
    visibility: 'internal' | 'restricted';
    updated_at: string;
    updated_by_user?: { public_id: string; name: string };
};

export type DocumentationStatus = 'empty' | 'partial' | 'complete';
