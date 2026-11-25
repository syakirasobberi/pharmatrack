/**
 * Turns the text of a check-up / lab report into form readings.
 *
 * Works line by line (a report row is "Label  value  unit  range"), matches labels as
 * whole words, converts mg/dL, g/L and mmol/mol to the units the form uses, and drops
 * anything outside a plausible range instead of guessing. Used by the check-up form
 * for both OCR text and the text layer of digital PDFs.
 */
(function (root) {
    'use strict';

    const GLUCOSE_MG = 18.016;  // mg/dL per mmol/L
    const LIPID_MG = 38.67;     // mg/dL per mmol/L (cholesterol, LDL, HDL)

    const mgToMmol = (factor) => (v) => v / factor;

    // Order matters: the first field whose label matches a line claims that line,
    // so specific labels (HDL, HbA1c, urine glucose) come before generic ones.
    const FIELDS = [
        {
            key: 'hba1c', label: 'HbA1c', min: 3, max: 20,
            labels: [/\bh\s?b\s?a\s?1\s?c\b/i, /\bglycated\s+ha?emoglobin\b/i, /\bglycosylated\s+ha?emoglobin\b/i, /\ba1c\b/i],
            units: { 'mmol/mol': (v) => v / 10.929 + 2.15 },
            infer: { unit: 'mmol/mol', from: 20, to: 200 },
        },
        {
            key: 'albumin_globulin_ratio', label: 'A/G ratio', min: 0.1, max: 10, ratioOk: true,
            labels: [/\ba\s?\/\s?g\s+ratio\b/i, /\balbumin\s*[\/\-:]?\s*globulin\b/i, /\bag\s+ratio\b/i],
        },
        {
            key: 'renal_glucose', label: 'Urine glucose', min: 0, max: 50, urineOk: true,
            labels: [/\b(?:urine|urinary|renal)\s+(?:glucose|sugar)\b/i, /\bglucose\s*\(\s*urine\s*\)/i],
            units: { 'mg/dl': mgToMmol(GLUCOSE_MG) },
        },
        {
            key: 'hdl', label: 'HDL', min: 0.2, max: 10,
            labels: [/\bhdl\b/i, /\bhigh[\s-]+density\s+lipoprotein\b/i],
            units: { 'mg/dl': mgToMmol(LIPID_MG) },
            infer: { unit: 'mg/dl', from: 10, to: 200 },
        },
        {
            key: 'ldl', label: 'LDL', min: 0.2, max: 20,
            labels: [/\bldl\b/i, /\blow[\s-]+density\s+lipoprotein\b/i],
            units: { 'mg/dl': mgToMmol(LIPID_MG) },
            infer: { unit: 'mg/dl', from: 20, to: 500 },
        },
        {
            key: 'cholesterol', label: 'Cholesterol', min: 1, max: 30,
            labels: [/\btotal\s+cholesterol\b/i, /\bcholesterol\b/i, /\bt\.?\s?chol\b/i],
            units: { 'mg/dl': mgToMmol(LIPID_MG) },
            infer: { unit: 'mg/dl', from: 40, to: 800 },
        },
        {
            key: 'blood_sugar', label: 'Blood sugar', min: 1, max: 50,
            labels: [/\bglucose\b/i, /\bblood\s+sugar\b/i, /\b(?:fbs|rbs|fbg|rbg|fpg)\b/i],
            units: { 'mg/dl': mgToMmol(GLUCOSE_MG) },
            infer: { unit: 'mg/dl', from: 50, to: 900 },
        },
        {
            key: 'haemoglobin', label: 'Haemoglobin', min: 3, max: 25,
            labels: [/\bha?emoglobin\b/i, /\bhgb\b/i, /\bhb\b/i],
            units: { 'g/l': (v) => v / 10 },
            infer: { unit: 'g/l', from: 30, to: 250 },
        },
        {
            key: 'alkaline_phosphatase', label: 'ALP', min: 1, max: 2000,
            labels: [/\balkaline\s+phosphatase\b/i, /\balk\.?\s+phos\w*/i, /\balp\b/i],
        },
        {
            key: 'aspartate_transaminase', label: 'AST', min: 1, max: 2000,
            labels: [/\baspartate\s+(?:transaminase|aminotransferase)\b/i, /\bast\b/i, /\bsgot\b/i],
        },
        {
            key: 'alanine_transaminase', label: 'ALT', min: 1, max: 2000,
            labels: [/\balanine\s+(?:transaminase|aminotransferase)\b/i, /\balt\b/i, /\bsgpt\b/i],
        },
        {
            key: 'gamma_glutamyl_transferase', label: 'GGT', min: 1, max: 2000,
            labels: [/\bgamma[\s-]*glutamyl\s*(?:transferase|transpeptidase)?/i, /\bgamma\s?gt\b/i, /\bg\.?g\.?t\b/i],
        },
        {
            key: 'sodium', label: 'Sodium', min: 100, max: 200,
            labels: [/\bsodium\b/i, /\bnatrium\b/i, /\bna\b\+?/i],
        },
        {
            key: 'heart_rate', label: 'Heart rate', min: 30, max: 250, integer: true,
            labels: [/\bheart\s+rate\b/i, /\bpulse(?:\s+rate)?\b/i, /\bhr\b/i],
        },
    ];

    const UNIT_PATTERN = /(mg\s?\/\s?d[li1]|mmol\s?\/\s?mol|mmol\s?\/\s?[li1]|g\s?\/\s?d[li1]|g\s?\/\s?[li1]\b|%|i?u\s?\/\s?[li1]\b|bpm)/i;

    function canonicalUnit(raw) {
        const u = raw.toLowerCase().replace(/\s/g, '').replace(/[i1]$/, 'l');
        if (u.startsWith('mg/d')) return 'mg/dl';
        if (u === 'mmol/mol') return 'mmol/mol';
        if (u.startsWith('mmol/')) return 'mmol/l';
        if (u.startsWith('g/d')) return 'g/dl';
        if (u === 'g/l') return 'g/l';
        return u;
    }

    /** Fix common OCR slips while keeping one report row per line. */
    function normalizeLines(text) {
        return String(text || '')
            .replace(/\r\n?/g, '\n')
            .split('\n')
            .map((line) => line
                .replace(/[，]/g, ',')
                .replace(/(\d)[|Il](?=\d)/g, (_, d) => d + '1')
                .replace(/(\d)[oO](?=\d|\b)/g, (_, d) => d + '0')
                .replace(/\b[oO](?=\.\d)/g, '0')
                .replace(/\b[lI](?=\.\d)/g, '1')
                .replace(/\bmmo[1lI]\b/gi, 'mmol')
                .replace(/(\d),(\d)/g, '$1.$2')
                .replace(/[|¦]/g, ' ')
                .replace(/[ \t]+/g, ' ')
                .trim())
            .filter((line) => line !== '');
    }

    const NUMBER = /(^|[^\d.\/])(\d{1,4}(?:\.\d{1,3})?)(?![\d\/])/g;

    /** First result value in a piece of text, skipping reference ranges like "3.9 - 6.1". */
    function firstValue(segment) {
        NUMBER.lastIndex = 0;
        let m;
        while ((m = NUMBER.exec(segment)) !== null) {
            const start = m.index + m[1].length;
            const end = start + m[2].length;
            const before = segment.slice(0, start);
            const after = segment.slice(end);
            if (/^\s*[-–~]\s*\d/.test(after) || /\d\s*[-–~]\s*$/.test(before)) continue; // part of a range
            if (/[<>≤≥]\s*$/.test(before)) continue;                                      // "< 5.2" reference
            const unit = after.match(UNIT_PATTERN);
            return { value: parseFloat(m[2]), raw: m[2], unit: unit ? canonicalUnit(unit[1]) : null };
        }
        return null;
    }

    function convert(field, found) {
        let { value, unit } = found;
        let note = null;
        const conversions = field.units || {};

        if (unit && conversions[unit]) {
            note = `${field.label} converted from ${found.raw} ${unit.replace('dl', 'dL').replace('/l', '/L')}`;
            value = conversions[unit](value);
        } else if (!unit && field.infer && value > field.max && value >= field.infer.from && value <= field.infer.to) {
            // No unit printed, and the number only makes sense in the other common unit.
            const u = field.infer.unit;
            note = `${field.label} ${found.raw} looks like ${u.replace('dl', 'dL').replace('/l', '/L')}; converted`;
            value = conversions[u](value);
        }

        if (!(value >= field.min && value <= field.max)) return null;
        value = field.integer ? Math.round(value) : Math.round(value * 100) / 100;
        return { value, note };
    }

    function findBloodPressure(lines) {
        const pair = /(?<![\d\/.])(\d{2,3})\s*[\/\\]\s*(\d{2,3})(?![\d.]|\s*[\/\\]\s*\d)/;
        const valid = (s, d) => s >= 70 && s <= 250 && d >= 40 && d <= 150 && s > d;

        for (const line of lines) {
            if (!/\b(?:bp|b\.p\.?|blood\s+pressure)\b|mm\s?hg/i.test(line)) continue;
            const m = line.match(pair);
            if (m && valid(+m[1], +m[2])) return `${+m[1]}/${+m[2]}`;
        }

        const text = lines.join('\n');
        const sys = text.match(/\bsys(?:tolic)?\b[^\d\n]{0,20}(\d{2,3})/i);
        const dia = text.match(/\bdia(?:stolic)?\b[^\d\n]{0,20}(\d{2,3})/i);
        if (sys && dia && valid(+sys[1], +dia[1])) return `${+sys[1]}/${+dia[1]}`;

        // Unlabelled "128/82" (e.g. a home BP monitor photo): only when there is exactly one.
        const all = lines.map((l) => l.match(pair)).filter((m) => m && valid(+m[1], +m[2]));
        if (all.length === 1) return `${+all[0][1]}/${+all[0][2]}`;

        return null;
    }

    /**
     * @param {string} text raw OCR or PDF text
     * @returns {{values: Object<string, number|string>, notes: string[]}}
     */
    function parse(text) {
        const lines = normalizeLines(text);
        const values = {};
        const notes = [];

        const bp = findBloodPressure(lines);
        if (bp) values.blood_pressure = bp;

        lines.forEach((line, i) => {
            const isRatio = /\bratio\b/i.test(line);
            const isUrine = /\b(?:urine|urinary|renal)\b/i.test(line);
            if (/\bnon[\s-]?hdl\b/i.test(line)) return;

            for (const field of FIELDS) {
                if (isRatio && !field.ratioOk) continue;
                if (isUrine && !field.urineOk && field.key !== 'albumin_globulin_ratio') continue;

                let label = null;
                for (const re of field.labels) {
                    const m = line.match(re);
                    if (m) { label = m; break; }
                }
                if (!label) continue;

                // This field claims the line, even if a value was already found earlier.
                if (values[field.key] !== undefined) return;

                const rest = line.slice(label.index + label[0].length);
                let found = firstValue(rest);
                // Tables sometimes OCR the value onto the next line.
                if (!found && lines[i + 1] && /^[<>]?\s*\d/.test(lines[i + 1])) found = firstValue(lines[i + 1]);
                if (!found) return;

                const result = convert(field, found);
                if (result) {
                    values[field.key] = result.value;
                    if (result.note) notes.push(result.note);
                }
                return;
            }
        });

        if (values.heart_rate === undefined) {
            const bpm = lines.join(' ').match(/(?<![\d.])(\d{2,3})\s?bpm\b/i);
            if (bpm && +bpm[1] >= 30 && +bpm[1] <= 250) values.heart_rate = +bpm[1];
        }

        return { values, notes };
    }

    const ReportParser = { parse, normalizeLines, FIELDS };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = ReportParser;
    } else {
        root.ReportParser = ReportParser;
    }
})(typeof window !== 'undefined' ? window : globalThis);
