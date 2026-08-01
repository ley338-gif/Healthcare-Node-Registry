import type { DocumentationSection, DocumentationStatus, RegistryDocumentationItem } from './documentationTypes.ts';

export const hasDocumentationValue = (value: unknown): boolean =>
    typeof value === 'boolean' || (typeof value === 'string' && value.trim() !== '');

export const documentForSection = (
    section: DocumentationSection,
    documentation: RegistryDocumentationItem[],
): RegistryDocumentationItem | undefined => documentation.find((item) => item.section === section.key);

export const sectionCounts = (
    section: DocumentationSection,
    document?: RegistryDocumentationItem,
): { filled: number; total: number; requiredFilled: number; requiredTotal: number } => {
    const filled = section.fields.filter((field) => hasDocumentationValue(document?.structured_data[field.key])).length;
    const required = section.fields.filter((field) => field.required);

    return {
        filled,
        total: section.fields.length,
        requiredFilled: required.filter((field) => hasDocumentationValue(document?.structured_data[field.key])).length,
        requiredTotal: required.length,
    };
};

export const sectionStatus = (
    section: DocumentationSection,
    document?: RegistryDocumentationItem,
): DocumentationStatus => {
    const counts = sectionCounts(section, document);
    if (counts.filled === 0) return 'empty';
    if (counts.requiredTotal > 0) return counts.requiredFilled === counts.requiredTotal ? 'complete' : 'partial';

    return counts.filled === counts.total ? 'complete' : 'partial';
};

export const documentationCompleteness = (
    sections: DocumentationSection[],
    documentation: RegistryDocumentationItem[],
): {
    requiredFilled: number;
    requiredTotal: number;
    percentage: number | null;
    label: 'Nicht begonnen' | 'Unvollständig' | 'Weitgehend vollständig' | 'Vollständig' | 'Nicht bewertet';
} => {
    const requiredTotal = sections.flatMap((section) => section.fields).filter((field) => field.required).length;
    const requiredFilled = sections.reduce((total, section) => {
        const document = documentForSection(section, documentation);
        return total + sectionCounts(section, document).requiredFilled;
    }, 0);

    if (requiredTotal === 0) {
        return { requiredFilled, requiredTotal, percentage: null, label: 'Nicht bewertet' };
    }

    const percentage = Math.round((requiredFilled / requiredTotal) * 100);
    const label =
        percentage === 0
            ? 'Nicht begonnen'
            : percentage < 60
              ? 'Unvollständig'
              : percentage < 100
                ? 'Weitgehend vollständig'
                : 'Vollständig';

    return { requiredFilled, requiredTotal, percentage, label };
};
