import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import { addDaysToIsoDate, resolveWorklistDateRange } from './dicomWorklistDatePresets.ts';

describe('dicom worklist date presets', () => {
    it('adds days across month/year boundaries', () => {
        assert.equal(addDaysToIsoDate('2026-08-08', 1), '2026-08-09');
        assert.equal(addDaysToIsoDate('2026-08-31', 1), '2026-09-01');
        assert.equal(addDaysToIsoDate('2026-12-31', 1), '2027-01-01');
    });

    it('resolves "today" to the current date with no end date', () => {
        assert.deepEqual(resolveWorklistDateRange('today', '2026-08-08', { from: '', to: '' }), {
            examinationDate: '2026-08-08',
            examinationDateTo: '',
        });
    });

    it('resolves "tomorrow" to today + 1 day with no end date', () => {
        assert.deepEqual(resolveWorklistDateRange('tomorrow', '2026-08-08', { from: '', to: '' }), {
            examinationDate: '2026-08-09',
            examinationDateTo: '',
        });
    });

    it('resolves "custom" to the given from/to values', () => {
        assert.deepEqual(resolveWorklistDateRange('custom', '2026-08-08', { from: '2026-08-10', to: '2026-08-12' }), {
            examinationDate: '2026-08-10',
            examinationDateTo: '2026-08-12',
        });
    });

    it('falls back to today when "custom" has no from date', () => {
        assert.deepEqual(resolveWorklistDateRange('custom', '2026-08-08', { from: '', to: '2026-08-12' }), {
            examinationDate: '2026-08-08',
            examinationDateTo: '2026-08-12',
        });
    });
});
