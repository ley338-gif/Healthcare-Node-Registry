export type WorklistDateMode = 'today' | 'tomorrow' | 'custom';

export type WorklistDateRange = {
    examinationDate: string;
    examinationDateTo: string;
};

/** Adds `days` (may be negative) to an ISO `YYYY-MM-DD` date string, returning an ISO date string. */
export function addDaysToIsoDate(isoDate: string, days: number): string {
    const date = new Date(`${isoDate}T00:00:00Z`);
    date.setUTCDate(date.getUTCDate() + days);

    return date.toISOString().slice(0, 10);
}

/**
 * Resolves the "Zeitraum" preset (Heute/Morgen/Benutzerdefiniert) into the
 * examination_date / examination_date_to values expected by the worklist form.
 */
export function resolveWorklistDateRange(
    mode: WorklistDateMode,
    today: string,
    custom: { from: string; to: string },
): WorklistDateRange {
    if (mode === 'today') {
        return { examinationDate: today, examinationDateTo: '' };
    }

    if (mode === 'tomorrow') {
        return { examinationDate: addDaysToIsoDate(today, 1), examinationDateTo: '' };
    }

    return { examinationDate: custom.from || today, examinationDateTo: custom.to };
}
