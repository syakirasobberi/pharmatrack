<?php

namespace App\Support;

use App\Models\Patient;
use Carbon\Carbon;

class HealthSummaryPdf
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MARGIN = 48;

    private array $pages = [];
    private string $content = '';
    private float $y = 0;

    public static function make(Patient $patient): string
    {
        $pdf = new self();
        $pdf->build($patient);

        return $pdf->render();
    }

    private function build(Patient $patient): void
    {
        $latestCheckup = $patient->healthCheckups->first();
        $assessment = $this->assessment($patient);
        $patientName = $this->value($patient->user?->name, 'Patient #' . $patient->id);
        $patientEmail = $this->value($patient->user?->email, 'No email recorded');

        $this->addPage();
        $this->reportHeader($patientName, $assessment['status']);
        $this->patientSnapshot($patient, $patientEmail, $latestCheckup);

        $this->trendSection($patient);

        $this->section('Overall Summary');
        $this->row('Status', $assessment['status']);
        $this->paragraph($assessment['summary']);
        $this->spacer(6);

        $this->section('Need To Take Care');
        $this->bullets($assessment['care_points']);

        $aiSuggestion = $this->suggestionItems($latestCheckup?->ai_suggestion);

        if ($aiSuggestion !== []) {
            $this->section('AI Health Insights');
            $this->suggestionCards($aiSuggestion);
        }

        $this->latestCheckupTable($latestCheckup);

        $this->section('Recent Check-up History');
        if ($patient->healthCheckups->isEmpty()) {
            $this->paragraph('No check-up history available.');
        } else {
            $items = $patient->healthCheckups->take(5)->map(function ($checkup) {
                return Carbon::parse($checkup->checkup_date)->format('d M Y')
                    . ': BP ' . $this->value($checkup->blood_pressure)
                    . ', Sugar ' . $this->withUnit($checkup->blood_sugar, 'mmol/L')
                    . ', Cholesterol ' . $this->withUnit($checkup->cholesterol, 'mmol/L');
            })->all();

            $this->bullets($items);
        }
    }

    private function reportHeader(string $patientName, string $status): void
    {
        $this->filledBox(0, 748, self::PAGE_WIDTH, 94, '0.10 0.24 0.57');
        $this->filledBox(0, 748, self::PAGE_WIDTH, 8, '0.05 0.58 0.70');
        $this->coloredText('PharmaTrack', self::MARGIN, 812, 13, 'F2', '1 1 1');
        $this->coloredText('Patient Health Summary', self::MARGIN, 786, 23, 'F2', '1 1 1');
        $this->coloredText($patientName, self::MARGIN, 766, 12, 'F2', '0.90 0.95 1.00');
        $this->coloredText('Generated on ' . now()->format('d M Y h:i A'), 395, 812, 8, 'F1', '0.86 0.92 1.00');

        [$statusFill, $statusText] = $this->statusColors($status);
        $this->box(395, 780, 130, 24, $statusFill, '0.75 0.84 1.00');
        $this->coloredText($status, 407, 788, 9, 'F2', $statusText);

        $this->y = 728;
    }

    private function patientSnapshot(Patient $patient, string $email, mixed $latestCheckup): void
    {
        $panelHeight = 68;
        $panelWidth = self::PAGE_WIDTH - (self::MARGIN * 2);
        $panelBottom = $this->y - $panelHeight;
        $cardWidth = ($panelWidth - 24) / 4;

        $this->box(self::MARGIN, $panelBottom, $panelWidth, $panelHeight, '0.97 0.98 1.00', '0.82 0.87 0.93');

        $items = [
            ['Email', $email],
            ['Demographics', $this->value($patient->gender) . ', ' . $this->value($patient->age) . ' years'],
            ['Height / Weight', $this->value($patient->height) . ' cm / ' . $this->value($patient->weight) . ' kg'],
            ['BMI', is_numeric($patient->bmi) ? number_format((float) $patient->bmi, 1) : 'N/A'],
        ];

        foreach ($items as $index => [$label, $value]) {
            $x = self::MARGIN + 12 + ($index * ($cardWidth + 4));
            $this->coloredText(strtoupper($label), $x, $this->y - 22, 6, 'F2', '0.33 0.42 0.55');

            $lineY = $this->y - 38;
            foreach (array_slice($this->wrap($value, 8, $cardWidth - 8), 0, 2) as $line) {
                $this->coloredText($line, $x, $lineY, 8, 'F2', '0.05 0.12 0.22');
                $lineY -= 10;
            }
        }

        if ($latestCheckup) {
            $this->coloredText(
                'Latest check-up: ' . Carbon::parse($latestCheckup->checkup_date)->format('d M Y'),
                self::MARGIN + 12,
                $panelBottom + 10,
                7,
                'F1',
                '0.37 0.45 0.57'
            );
        }

        $this->y = $panelBottom - 14;
    }

    private function latestCheckupTable(mixed $latestCheckup): void
    {
        $this->section('Latest Check-up');

        if (! $latestCheckup) {
            $this->paragraph('No latest check-up data available.');
            return;
        }

        $rows = [
            ['Check-up Date', Carbon::parse($latestCheckup->checkup_date)->format('d M Y'), 'Blood Pressure', $this->value($latestCheckup->blood_pressure)],
            ['Heart Rate', $this->withUnit($latestCheckup->heart_rate, 'bpm'), 'Haemoglobin', $this->withUnit($latestCheckup->haemoglobin, 'g/dL')],
            ['Blood Sugar', $this->withUnit($latestCheckup->blood_sugar, 'mmol/L'), 'HbA1c', $this->withUnit($latestCheckup->hba1c, '%')],
            ['Cholesterol', $this->withUnit($latestCheckup->cholesterol, 'mmol/L'), 'LDL / HDL', $this->withUnit($latestCheckup->ldl, 'mmol/L') . ' / ' . $this->withUnit($latestCheckup->hdl, 'mmol/L')],
            ['Liver Function', 'A/G ' . $this->value($latestCheckup->albumin_globulin_ratio) . ', ALP ' . $this->withUnit($latestCheckup->alkaline_phosphatase, 'U/L') . ', AST ' . $this->withUnit($latestCheckup->aspartate_transaminase, 'U/L'), 'ALT / GGT', $this->withUnit($latestCheckup->alanine_transaminase, 'U/L') . ' / ' . $this->withUnit($latestCheckup->gamma_glutamyl_transferase, 'U/L')],
            ['Renal Function', 'Sodium ' . $this->withUnit($latestCheckup->sodium, 'mmol/L') . ', Glucose ' . $this->withUnit($latestCheckup->renal_glucose, 'mmol/L'), '', ''],
        ];

        $rowHeight = 28;
        $tableHeight = count($rows) * $rowHeight;
        $this->ensureSpace($tableHeight + 8);

        $x = self::MARGIN;
        $top = $this->y;
        $width = self::PAGE_WIDTH - (self::MARGIN * 2);
        $labelWidth = 78;
        $valueWidth = ($width / 2) - $labelWidth;

        $this->box($x, $top - $tableHeight, $width, $tableHeight, '1 1 1', '0.83 0.87 0.92');

        foreach ($rows as $index => $row) {
            $rowTop = $top - ($index * $rowHeight);
            $rowBottom = $rowTop - $rowHeight;

            if ($index % 2 === 0) {
                $this->filledBox($x, $rowBottom, $width, $rowHeight, '0.97 0.98 1.00');
            }

            $this->strokeLine($x, $rowBottom, $x + $width, $rowBottom, '0.88 0.91 0.95', 0.4);
            $this->tableCell($row[0], $row[1], $x + 10, $rowTop - 11, $labelWidth, $valueWidth);

            if ($row[2] !== '') {
                $this->tableCell($row[2], $row[3], $x + ($width / 2) + 10, $rowTop - 11, $labelWidth, $valueWidth);
            }
        }

        $this->y -= $tableHeight + 4;
    }

    private function tableCell(string $label, string $value, float $x, float $y, float $labelWidth, float $valueWidth): void
    {
        $this->coloredText($label, $x, $y, 7, 'F2', '0.33 0.42 0.55');

        $lineY = $y;
        foreach (array_slice($this->wrap($value, 8, $valueWidth), 0, 2) as $line) {
            $this->coloredText($line, $x + $labelWidth, $lineY, 8, 'F1', '0.07 0.13 0.22');
            $lineY -= 9;
        }
    }

    private function statusColors(string $status): array
    {
        return match ($status) {
            'Stable' => ['0.86 0.97 0.91', '0.03 0.45 0.25'],
            'Needs close monitoring' => ['1.00 0.90 0.90', '0.68 0.10 0.10'],
            default => ['1.00 0.96 0.80', '0.62 0.30 0.00'],
        };
    }

    private function assessment(Patient $patient): array
    {
        $latest = $patient->healthCheckups->first();
        $concerns = [];
        $carePoints = [];

        if (! $latest) {
            return [
                'status' => 'Needs check-up data',
                'summary' => 'No health check-up record is available yet. Please complete a check-up with the pharmacy so the system can track health changes over time.',
                'care_points' => [
                    'Complete a routine check-up to record blood pressure, blood sugar, cholesterol, and other important readings.',
                    'Keep your medication list updated at least every 6 months.',
                ],
            ];
        }

        [$systolic, $diastolic] = $this->bloodPressureParts($latest->blood_pressure);

        if (is_numeric($patient->bmi) && (float) $patient->bmi >= 25) {
            $concerns[] = 'BMI';
            $carePoints[] = 'Monitor weight, meal portions, and physical activity.';
        }

        if (is_numeric($latest->blood_sugar) && ((float) $latest->blood_sugar < 3.9 || (float) $latest->blood_sugar > 6.0)) {
            $concerns[] = 'blood sugar';
            $carePoints[] = 'Watch sugar intake, meal timing, and diabetes follow-up.';
        }

        if (is_numeric($latest->hba1c) && (float) $latest->hba1c >= 5.7) {
            $concerns[] = 'HbA1c';
            $carePoints[] = 'Review long-term blood sugar control with your healthcare provider.';
        }

        if (is_numeric($latest->cholesterol) && (float) $latest->cholesterol >= 5.2) {
            $concerns[] = 'cholesterol';
            $carePoints[] = 'Review cholesterol control, diet, exercise, and lipid follow-up.';
        }

        if (is_numeric($latest->ldl) && (float) $latest->ldl >= 2.6) {
            $concerns[] = 'LDL';
            $carePoints[] = 'Pay attention to heart health and high LDL cholesterol.';
        }

        if (($systolic && $systolic >= 130) || ($diastolic && $diastolic >= 80)) {
            $concerns[] = 'blood pressure';
            $carePoints[] = 'Monitor blood pressure regularly and reduce excess salt intake.';
        }

        $concerns = array_values(array_unique($concerns));
        $carePoints = array_values(array_unique($carePoints));

        if (count($concerns) >= 3) {
            $status = 'Needs close monitoring';
        } elseif (count($concerns) > 0) {
            $status = 'Needs monitoring';
        } else {
            $status = 'Stable';
        }

        if (empty($carePoints)) {
            $carePoints[] = 'Continue routine check-ups and maintain healthy daily habits.';
        }

        $carePoints[] = 'Keep your medication list updated at least every 6 months.';

        return [
            'status' => $status,
            'summary' => empty($concerns)
                ? 'Latest readings do not show major warning signs based on the recorded data.'
                : 'Latest readings need attention for ' . implode(', ', $concerns) . '. Continue monitoring and follow pharmacist or doctor advice.',
            'care_points' => $carePoints,
        ];
    }

    private function trendSection(Patient $patient): void
    {
        $metrics = [
            [
                'key' => 'blood_sugar',
                'label' => 'Blood Sugar',
                'unit' => 'mmol/L',
                'normal_label' => 'Normal: 3.9-6.0 mmol/L',
                'target' => 6.0,
                'lower' => 3.9,
            ],
            [
                'key' => 'cholesterol',
                'label' => 'Cholesterol',
                'unit' => 'mmol/L',
                'normal_label' => 'Target: below 5.2 mmol/L',
                'target' => 5.2,
            ],
            [
                'key' => 'hba1c',
                'label' => 'HbA1c',
                'unit' => '%',
                'normal_label' => 'Normal: below 5.7%',
                'target' => 5.7,
            ],
            [
                'key' => 'ldl',
                'label' => 'LDL',
                'unit' => 'mmol/L',
                'normal_label' => 'Target: below 2.6 mmol/L',
                'target' => 2.6,
            ],
        ];

        $this->section('Health Trend Comparison');
        $this->paragraph('Small bar graphs compare recent check-ups with the latest reading. The green line marks the normal or target range for each category.');

        $rowHeight = 124;

        foreach (array_chunk($metrics, 2) as $row) {
            $this->ensureSpace($rowHeight);
            $top = $this->y;

            foreach ($row as $index => $metric) {
                $this->drawMetricChart($patient, $metric, self::MARGIN + ($index * 255), $top);
            }

            $this->y -= $rowHeight;
        }
    }

    private function drawMetricChart(Patient $patient, array $metric, float $x, float $top): void
    {
        $chartX = $x + 6;
        $chartY = $top - 72;
        $chartWidth = 220;
        $chartHeight = 44;
        $readings = $patient->healthCheckups
            ->sortBy('checkup_date')
            ->filter(fn ($checkup) => is_numeric($checkup->{$metric['key']}))
            ->map(fn ($checkup) => [
                'date' => Carbon::parse($checkup->checkup_date)->format('d M'),
                'value' => (float) $checkup->{$metric['key']},
            ])
            ->slice(-5)
            ->values();

        $this->text($metric['label'], $x, $top, 11, 'F2');
        $this->text($metric['normal_label'], $x, $top - 14, 8);
        $this->box($chartX, $chartY, $chartWidth, $chartHeight, '0.96 0.98 1.00', '0.78 0.84 0.91');

        if ($readings->isEmpty()) {
            $this->text('No data recorded', $chartX + 58, $chartY + 20, 8);
            return;
        }

        $values = $readings->pluck('value');
        $max = max((float) $metric['target'], (float) $values->max());
        $scaleMax = max(1, $max * 1.18);
        $targetY = $chartY + ($chartHeight * min(1, (float) $metric['target'] / $scaleMax));

        if (isset($metric['lower'])) {
            $lowerY = $chartY + ($chartHeight * min(1, (float) $metric['lower'] / $scaleMax));
            $this->filledBox($chartX, $lowerY, $chartWidth, max(2, $targetY - $lowerY), '0.88 0.97 0.92');
        }

        $slotWidth = $chartWidth / max(1, $readings->count());
        $barWidth = min(24, max(10, $slotWidth * 0.48));
        $latest = $readings->last();

        foreach ($readings as $index => $reading) {
            $barHeight = max(2, $chartHeight * min(1, $reading['value'] / $scaleMax));
            $barX = $chartX + ($slotWidth * $index) + (($slotWidth - $barWidth) / 2);
            $barColor = $this->metricBarColor($reading['value'], $metric);

            $this->filledBox($barX, $chartY, $barWidth, $barHeight, $barColor);
            $this->text($reading['date'], $barX - 3, $chartY - 12, 6);
        }

        if (isset($lowerY)) {
            $this->strokeLine($chartX, $lowerY, $chartX + $chartWidth, $lowerY, '0.10 0.55 0.34', 0.5);
        }

        $this->strokeLine($chartX, $targetY, $chartX + $chartWidth, $targetY, '0.10 0.55 0.34', 0.8);
        $this->text('normal', $chartX + $chartWidth - 30, $targetY + 3, 7);
        $this->text('Latest: ' . number_format($latest['value'], 1) . ' ' . $metric['unit'], $x, $chartY - 25, 8, 'F2');
    }

    private function metricBarColor(float $value, array $metric): string
    {
        if (isset($metric['lower']) && $value < (float) $metric['lower']) {
            return '0.86 0.45 0.08';
        }

        if ($value > (float) $metric['target']) {
            return '0.86 0.25 0.25';
        }

        return '0.15 0.35 0.75';
    }

    private function section(string $title): void
    {
        $this->spacer(10);
        $this->ensureSpace(28);
        $this->text($title, self::MARGIN, $this->y, 13, 'F2');
        $this->y -= 17;
    }

    private function row(string $label, string $value): void
    {
        $this->ensureSpace(18);
        $this->text($label . ':', self::MARGIN, $this->y, 9, 'F2');
        $this->paragraph($value, 190, 10, 12, 350, false);
    }

    private function paragraph(string $text, float $x = self::MARGIN, int $size = 10, int $lineHeight = 13, float $width = 499, bool $moveDownFirst = true): void
    {
        if ($moveDownFirst) {
            $this->ensureSpace($lineHeight);
        }

        foreach ($this->wrap($text, $size, $width) as $line) {
            $this->ensureSpace($lineHeight);
            $this->text($line, $x, $this->y, $size);
            $this->y -= $lineHeight;
        }
    }

    private function bullets(array $items): void
    {
        foreach ($items as $item) {
            foreach ($this->wrap((string) $item, 10, 482) as $index => $line) {
                $this->ensureSpace(13);
                $this->text($index === 0 ? '-' : ' ', self::MARGIN, $this->y, 10);
                $this->text($line, self::MARGIN + 14, $this->y, 10);
                $this->y -= 13;
            }
        }
    }

    private function suggestionItems(mixed $suggestion): array
    {
        if (! is_string($suggestion) || trim($suggestion) === '') {
            return [];
        }

        $lines = preg_split('/\R+|(?=\d+\.\s+[A-Za-z])/', $suggestion) ?: [];
        $items = [];

        foreach ($lines as $line) {
            $line = trim(str_replace('*', '', $line));
            $line = preg_replace('/^\d+\.\s*/', '', $line);

            if ($line !== '') {
                $items[] = $line;
            }
        }

        return array_slice($items, 0, 6);
    }

    private function suggestionCards(array $items): void
    {
        $cards = $this->suggestionCardsData($items);
        $panelHeight = 170;
        $panelX = self::MARGIN;
        $panelWidth = self::PAGE_WIDTH - (self::MARGIN * 2);
        $cardWidth = 232;
        $cardHeight = 58;
        $gap = 14;

        $this->ensureSpace($panelHeight + 8);
        $top = $this->y;

        $this->box($panelX, $top - $panelHeight, $panelWidth, $panelHeight, '0.93 0.95 1.00', '0.82 0.86 0.98');

        foreach ($cards as $index => $card) {
            $col = $index % 2;
            $row = intdiv($index, 2);
            $x = $panelX + 16 + ($col * ($cardWidth + $gap));
            $cardTop = $top - 18 - ($row * ($cardHeight + 12));
            $y = $cardTop - $cardHeight;

            $this->box($x, $y, $cardWidth, $cardHeight, $card['fill'], $card['stroke']);
            $this->coloredText(strtoupper($card['title']), $x + 12, $cardTop - 17, 7, 'F2', $card['text']);

            $lineY = $cardTop - 34;
            foreach (array_slice($this->wrap($card['body'], 8, $cardWidth - 24), 0, 3) as $line) {
                $this->coloredText($line, $x + 12, $lineY, 8, 'F1', $card['text']);
                $lineY -= 10;
            }
        }

        $this->coloredText(
            'Medication notes are for pharmacist review only. The system does not automatically prescribe medication.',
            $panelX + 16,
            $top - $panelHeight + 16,
            8,
            'F1',
            '0.17 0.21 0.77'
        );

        $this->y -= $panelHeight + 8;
    }

    private function suggestionCardsData(array $items): array
    {
        $styles = [
            'food' => ['fill' => '0.91 0.98 0.94', 'stroke' => '0.74 0.92 0.80', 'text' => '0.00 0.36 0.20'],
            'exercise' => ['fill' => '0.92 0.96 1.00', 'stroke' => '0.78 0.87 0.98', 'text' => '0.04 0.20 0.54'],
            'follow-up' => ['fill' => '1.00 0.98 0.90', 'stroke' => '0.96 0.86 0.58', 'text' => '0.55 0.20 0.00'],
            'medication review' => ['fill' => '0.98 0.94 1.00', 'stroke' => '0.91 0.81 0.96', 'text' => '0.38 0.08 0.54'],
        ];

        $cards = [];

        foreach ($items as $item) {
            [$title, $body] = $this->suggestionTitleBody((string) $item);
            $key = strtolower($title);

            if (isset($styles[$key])) {
                $cards[$key] = array_merge(['title' => $title, 'body' => $body], $styles[$key]);
            }
        }

        $ordered = [];

        foreach (['food', 'exercise', 'follow-up', 'medication review'] as $key) {
            if (! isset($cards[$key])) {
                $cards[$key] = array_merge([
                    'title' => ucwords($key),
                    'body' => 'No recommendation recorded.',
                ], $styles[$key]);
            }

            $ordered[] = $cards[$key];
        }

        return $ordered;
    }

    private function suggestionTitleBody(string $item): array
    {
        $item = trim($item);

        if (str_contains($item, ':')) {
            [$title, $body] = array_map('trim', explode(':', $item, 2));

            return [$this->suggestionTitle($title), $body !== '' ? $body : $item];
        }

        return ['Recommendation', $item];
    }

    private function suggestionTitle(string $title): string
    {
        $normalized = strtolower(trim($title));

        return match ($normalized) {
            'food' => 'Food',
            'exercise' => 'Exercise',
            'follow up', 'follow-up', 'followup' => 'Follow-up',
            'medication', 'medication review' => 'Medication Review',
            default => ucwords($normalized),
        };
    }

    private function spacer(float $space): void
    {
        $this->ensureSpace($space);
        $this->y -= $space;
    }

    private function addPage(): void
    {
        if ($this->content !== '') {
            $this->pages[] = $this->content;
        }

        $this->content = '';
        $this->y = 790;
    }

    private function ensureSpace(float $space): void
    {
        if ($this->y - $space < self::MARGIN) {
            $this->addPage();
        }
    }

    private function text(string $text, float $x, float $y, int $size = 10, string $font = 'F1'): void
    {
        $this->content .= "BT /{$font} {$size} Tf {$x} {$y} Td (" . $this->escape($text) . ") Tj ET\n";
    }

    private function coloredText(string $text, float $x, float $y, int $size = 10, string $font = 'F1', string $rgb = '0 0 0'): void
    {
        $this->content .= "q {$rgb} rg BT /{$font} {$size} Tf {$x} {$y} Td (" . $this->escape($text) . ") Tj ET Q\n";
    }

    private function line(float $y): void
    {
        $this->content .= '0.11 0.30 0.64 RG ' . self::MARGIN . " {$y} m " . (self::PAGE_WIDTH - self::MARGIN) . " {$y} l S\n";
    }

    private function strokeLine(float $x1, float $y1, float $x2, float $y2, string $rgb, float $width = 1): void
    {
        $this->content .= 'q ' . $rgb . ' RG ' . $this->num($width) . ' w '
            . $this->num($x1) . ' ' . $this->num($y1) . ' m '
            . $this->num($x2) . ' ' . $this->num($y2) . " l S Q\n";
    }

    private function box(float $x, float $y, float $width, float $height, string $fillRgb, string $strokeRgb): void
    {
        $this->content .= 'q ' . $fillRgb . ' rg ' . $strokeRgb . ' RG '
            . $this->num($x) . ' ' . $this->num($y) . ' '
            . $this->num($width) . ' ' . $this->num($height) . " re B Q\n";
    }

    private function filledBox(float $x, float $y, float $width, float $height, string $fillRgb): void
    {
        $this->content .= 'q ' . $fillRgb . ' rg '
            . $this->num($x) . ' ' . $this->num($y) . ' '
            . $this->num($width) . ' ' . $this->num($height) . " re f Q\n";
    }

    private function render(): string
    {
        if ($this->content !== '') {
            $this->pages[] = $this->content;
        }

        $pageCount = count($this->pages);
        $fontStart = 3;
        $firstPageObject = 5;
        $kids = [];
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];

        foreach ($this->pages as $index => $content) {
            $pageObject = $firstPageObject + ($index * 2);
            $contentObject = $pageObject + 1;
            $contentWithFooter = $content . $this->footerContent($index + 1, $pageCount);
            $kids[] = "{$pageObject} 0 R";
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 ' . $fontStart . ' 0 R /F2 4 0 R >> >> /Contents ' . $contentObject . ' 0 R >>';
            $objects[$contentObject] = '<< /Length ' . strlen($contentWithFooter) . " >>\nstream\n{$contentWithFooter}endstream";
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (max(array_keys($objects)) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= max(array_keys($objects)); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }

        $pdf .= "trailer\n<< /Size " . (max(array_keys($objects)) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function footerContent(int $page, int $pageCount): string
    {
        $left = $this->escape('Generated by PharmaTrack');
        $right = $this->escape('Page ' . $page . ' of ' . $pageCount);

        return '0.84 0.88 0.94 RG ' . self::MARGIN . ' 34 m ' . (self::PAGE_WIDTH - self::MARGIN) . " 34 l S\n"
            . "q 0.42 0.48 0.58 rg BT /F1 7 Tf " . self::MARGIN . " 22 Td ({$left}) Tj ET Q\n"
            . "q 0.42 0.48 0.58 rg BT /F1 7 Tf 500 22 Td ({$right}) Tj ET Q\n";
    }

    private function wrap(string $text, int $size, float $width): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $this->normalize($text)));

        if ($text === '') {
            return ['N/A'];
        }

        $maxChars = max(16, (int) floor($width / ($size * 0.48)));
        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = trim($line . ' ' . $word);
            if (strlen($candidate) > $maxChars && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->normalize($text));
    }

    private function normalize(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    private function num(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    private function value(mixed $value, string $fallback = 'N/A'): string
    {
        return filled($value) ? (string) $value : $fallback;
    }

    private function withUnit(mixed $value, string $unit): string
    {
        return filled($value) ? $value . ' ' . $unit : 'N/A';
    }

    private function bloodPressureParts(mixed $bloodPressure): array
    {
        if (! is_string($bloodPressure) || ! preg_match('/(\d{2,3})\D+(\d{2,3})/', $bloodPressure, $matches)) {
            return [null, null];
        }

        return [(int) $matches[1], (int) $matches[2]];
    }
}
