import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import { documentationCompleteness, sectionCounts, sectionStatus } from './documentationCompleteness.ts';
import type { DocumentationSection, RegistryDocumentationItem } from './documentationTypes.ts';

const section: DocumentationSection = {
    key: 'responsibility',
    title: 'IT-Verantwortung',
    description: 'Technische Zuständigkeiten',
    fields: [
        { key: 'owner', label: 'Verantwortlicher', required: true },
        { key: 'deputy', label: 'Vertretung', required: true },
        { key: 'contact', label: 'Kontakt' },
    ],
};

const document = (structuredData: Record<string, unknown>): RegistryDocumentationItem => ({
    public_id: '019c1234-0000-7000-8000-000000000001',
    documentation_type: 'operations',
    section: section.key,
    title: section.title,
    content: null,
    structured_data: structuredData,
    visibility: 'internal',
    updated_at: '2026-08-01T12:00:00+00:00',
});

describe('documentation completeness', () => {
    it('keeps an empty section compact and unmaintained', () => {
        assert.equal(sectionStatus(section), 'empty');
        assert.deepEqual(sectionCounts(section), {
            filled: 0,
            total: 3,
            requiredFilled: 0,
            requiredTotal: 2,
        });
    });

    it('distinguishes partially and fully maintained sections', () => {
        assert.equal(sectionStatus(section, document({ owner: 'Max Mustermann' })), 'partial');
        assert.equal(
            sectionStatus(section, document({ owner: 'Max Mustermann', deputy: 'Erika Musterfrau' })),
            'complete',
        );
    });

    it('calculates the overall status only from defined required fields', () => {
        assert.deepEqual(documentationCompleteness([section], [document({ owner: 'Max Mustermann' })]), {
            requiredFilled: 1,
            requiredTotal: 2,
            percentage: 50,
            label: 'Unvollständig',
        });
    });

    it('does not invent a percentage when no required fields exist', () => {
        const optionalSection = { ...section, fields: [{ key: 'notes', label: 'Hinweise' }] };
        assert.deepEqual(documentationCompleteness([optionalSection], []), {
            requiredFilled: 0,
            requiredTotal: 0,
            percentage: null,
            label: 'Nicht bewertet',
        });
    });
});
