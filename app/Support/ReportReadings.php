<?php

namespace App\Support;

/**
 * Cleans readings that the AI read off a report: converts units to the ones the
 * check-up form uses and drops anything outside a plausible range. Mirrors the
 * rules in public/js/report-parser.js.
 */
class ReportReadings
{
    private const GLUCOSE_MG = 18.016;
    private const LIPID_MG = 38.67;

    /**
     * field => [label, min, max, integer?, conversions (unit => divisor or callable)]
     */
    public const FIELDS = [
        'heart_rate'                 => ['Heart rate', 30, 250, true, []],
        'haemoglobin'                => ['Haemoglobin', 3, 25, false, ['g/l' => 10]],
        'blood_sugar'                => ['Blood sugar (blood glucose)', 1, 50, false, ['mg/dl' => self::GLUCOSE_MG]],
        'hba1c'                      => ['HbA1c', 3, 20, false, ['mmol/mol' => 'hba1c']],
        'cholesterol'                => ['Total cholesterol', 1, 30, false, ['mg/dl' => self::LIPID_MG]],
        'ldl'                        => ['LDL cholesterol', 0.2, 20, false, ['mg/dl' => self::LIPID_MG]],
        'hdl'                        => ['HDL cholesterol', 0.2, 10, false, ['mg/dl' => self::LIPID_MG]],
        'albumin_globulin_ratio'     => ['Albumin/globulin ratio', 0.1, 10, false, []],
        'alkaline_phosphatase'       => ['Alkaline phosphatase (ALP)', 1, 2000, false, []],
        'aspartate_transaminase'     => ['AST / SGOT', 1, 2000, false, []],
        'alanine_transaminase'       => ['ALT / SGPT', 1, 2000, false, []],
        'gamma_glutamyl_transferase' => ['Gamma GT (GGT)', 1, 2000, false, []],
        'sodium'                     => ['Sodium', 100, 200, false, []],
        'renal_glucose'              => ['Urine glucose', 0, 50, false, ['mg/dl' => self::GLUCOSE_MG]],
    ];

    /**
     * @param  array<string, mixed>  $raw  {blood_pressure: "120/80"|null, <field>: {value, unit}|null}
     * @return array{values: array<string, int|float|string>, notes: list<string>}
     */
    public static function normalize(array $raw): array
    {
        $values = [];
        $notes = [];

        $bp = is_string($raw['blood_pressure'] ?? null) ? $raw['blood_pressure'] : null;
        // Accept "142/91", "142/91 mmHg", "BP 142 / 91".
        if ($bp && preg_match('/(?<![\d\/])(\d{2,3})\s*\/\s*(\d{2,3})(?![\d\/])/', $bp, $m)) {
            [$sys, $dia] = [(int) $m[1], (int) $m[2]];
            if ($sys >= 70 && $sys <= 250 && $dia >= 40 && $dia <= 150 && $sys > $dia) {
                $values['blood_pressure'] = "{$sys}/{$dia}";
            }
        }

        foreach (self::FIELDS as $key => [$label, $min, $max, $integer, $conversions]) {
            $item = $raw[$key] ?? null;
            if (! is_array($item) || ! is_numeric($item['value'] ?? null)) {
                continue;
            }

            $value = (float) $item['value'];
            $unit = self::unit((string) ($item['unit'] ?? ''));

            if ($unit !== '' && isset($conversions[$unit])) {
                $original = $value;
                $value = $conversions[$unit] === 'hba1c'
                    ? $value / 10.929 + 2.15
                    : $value / $conversions[$unit];
                $notes[] = sprintf('%s converted from %s %s', $label, rtrim(rtrim(number_format($original, 2, '.', ''), '0'), '.'), $item['unit']);
            }

            if ($value < $min || $value > $max) {
                $notes[] = "{$label} skipped: value outside the expected range";
                continue;
            }

            $values[$key] = $integer ? (int) round($value) : round($value, 2);
        }

        return ['values' => $values, 'notes' => $notes];
    }

    private static function unit(string $unit): string
    {
        $u = strtolower(preg_replace('/\s+/', '', $unit));

        return match (true) {
            str_starts_with($u, 'mg/d') => 'mg/dl',
            $u === 'mmol/mol' => 'mmol/mol',
            $u === 'g/l' => 'g/l',
            default => $u,
        };
    }

    /** Gemini response schema: one nullable {value, unit} object per field. */
    public static function responseSchema(): array
    {
        $reading = [
            'type' => 'OBJECT',
            'nullable' => true,
            'properties' => [
                'value' => ['type' => 'NUMBER'],
                'unit' => ['type' => 'STRING', 'nullable' => true],
            ],
            'required' => ['value'],
        ];

        $properties = ['blood_pressure' => ['type' => 'STRING', 'nullable' => true, 'description' => 'Systolic/diastolic, e.g. 128/82']];
        foreach (self::FIELDS as $key => [$label]) {
            $properties[$key] = $reading + ['description' => $label];
        }

        return ['type' => 'OBJECT', 'properties' => $properties];
    }
}
