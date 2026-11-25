<?php

namespace App\Support;

use RuntimeException;

/**
 * Health risk model in pure PHP (no Python needed, so it runs on shared hosting).
 *
 * A CART decision tree like scikit-learn's DecisionTreeClassifier(max_depth=4):
 * binary splits on "feature <= threshold", chosen by the largest Gini impurity decrease,
 * thresholds halfway between neighbouring values. It is trained from health_data.csv,
 * so editing the CSV changes the model just as it did with predict.py.
 */
class RiskModel
{
    public const FEATURES = ['BMI', 'BloodSugar', 'BloodPressure', 'Cholesterol'];

    private const MAX_DEPTH = 4;

    /**
     * Order features are tried in; when two splits are equally good, the earlier feature wins.
     * This training data separates perfectly on ANY single feature, so the choice is arbitrary.
     * scikit-learn breaks such ties by its random seed (random_state=42); this order reproduces
     * the tree predict.py built, so predictions stay identical to the Python version.
     *
     * @var list<int> indexes into FEATURES
     */
    private static array $tieBreakOrder = [2, 0, 1, 3]; // BloodPressure, BMI, BloodSugar, Cholesterol

    /** @var array<string, array> trained trees, keyed by CSV path + modification time */
    private static array $trees = [];

    /**
     * Predict risk for one patient.
     *
     * @return array{risk: string, confidence: string, model_confidence: int}
     */
    public static function predict(float $bmi, float $bloodSugar, float $bloodPressure, float $cholesterol, ?string $csvPath = null): array
    {
        $tree = self::tree($csvPath ?? base_path('health_data.csv'));
        $leaf = self::leafFor($tree, [$bmi, $bloodSugar, $bloodPressure, $cholesterol]);

        // Like predict()/predict_proba(): majority class of the leaf, probability = its share.
        $total = array_sum($leaf['counts']);
        $risk = self::majority($leaf['counts']);
        $modelConfidence = self::roundHalfEven($leaf['counts'][$risk] / $total * 100);

        $confidence = self::realisticConfidence($risk, $modelConfidence, $bmi, $bloodSugar, $bloodPressure, $cholesterol);

        return ['risk' => $risk, 'confidence' => $confidence . '%', 'model_confidence' => $modelConfidence];
    }

    /** Train (once per request per CSV version) and return the tree. */
    public static function tree(string $csvPath): array
    {
        if (! is_readable($csvPath)) {
            throw new RuntimeException("Training data not found: {$csvPath}");
        }

        $key = $csvPath . '@' . filemtime($csvPath);

        return self::$trees[$key] ??= self::build(self::loadCsv($csvPath), 0);
    }

    /** @return list<array{x: list<float>, y: string}> */
    private static function loadCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = array_map('trim', fgetcsv($handle, escape: '\\'));
        $columns = array_map(fn ($name) => array_search($name, $header, true), [...self::FEATURES, 'Risk']);

        if (in_array(false, $columns, true)) {
            throw new RuntimeException('health_data.csv must have columns: ' . implode(', ', [...self::FEATURES, 'Risk']));
        }

        $rows = [];
        while (($line = fgetcsv($handle, escape: '\\')) !== false) {
            if ($line === [null] || count($line) < count($header)) {
                continue; // blank line
            }
            $x = [];
            foreach (array_slice($columns, 0, 4) as $col) {
                $x[] = (float) $line[$col];
            }
            $rows[] = ['x' => $x, 'y' => trim($line[$columns[4]])];
        }
        fclose($handle);

        if ($rows === []) {
            throw new RuntimeException('health_data.csv has no data rows.');
        }

        return $rows;
    }

    private static function build(array $rows, int $depth): array
    {
        $counts = self::counts($rows);
        $node = ['counts' => $counts];

        if ($depth >= self::MAX_DEPTH || count($counts) === 1 || count($rows) < 2) {
            return $node;
        }

        $split = self::bestSplit($rows, self::gini($counts));
        if ($split === null) {
            return $node;
        }

        [$feature, $threshold] = $split;
        $left = array_values(array_filter($rows, fn ($r) => $r['x'][$feature] <= $threshold));
        $right = array_values(array_filter($rows, fn ($r) => $r['x'][$feature] > $threshold));

        return $node + [
            'feature' => $feature,
            'threshold' => $threshold,
            'left' => self::build($left, $depth + 1),
            'right' => self::build($right, $depth + 1),
        ];
    }

    /** Best "feature <= threshold" split by Gini decrease; null if nothing improves purity. */
    private static function bestSplit(array $rows, float $parentGini): ?array
    {
        $n = count($rows);
        $best = null;
        $bestImpurity = $parentGini;

        foreach (self::$tieBreakOrder as $feature) {
            usort($rows, fn ($a, $b) => $a['x'][$feature] <=> $b['x'][$feature]);

            $leftCounts = [];
            $rightCounts = self::counts($rows);

            for ($i = 0; $i < $n - 1; $i++) {
                $label = $rows[$i]['y'];
                $leftCounts[$label] = ($leftCounts[$label] ?? 0) + 1;
                $rightCounts[$label]--;

                $value = $rows[$i]['x'][$feature];
                $next = $rows[$i + 1]['x'][$feature];
                if ($value === $next) {
                    continue; // can't split between equal values
                }

                $nl = $i + 1;
                $impurity = ($nl / $n) * self::gini($leftCounts) + (($n - $nl) / $n) * self::gini($rightCounts);

                // Strictly better only (with a tiny tolerance), so ties keep the first split found.
                if ($impurity < $bestImpurity - 1e-12) {
                    $bestImpurity = $impurity;
                    $best = [$feature, ($value + $next) / 2];
                }
            }
        }

        return $best;
    }

    private static function leafFor(array $node, array $x): array
    {
        while (isset($node['feature'])) {
            $node = $x[$node['feature']] <= $node['threshold'] ? $node['left'] : $node['right'];
        }

        return $node;
    }

    private static function counts(array $rows): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['y']] = ($counts[$row['y']] ?? 0) + 1;
        }

        return $counts;
    }

    private static function gini(array $counts): float
    {
        $total = array_sum($counts);
        if ($total === 0) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($counts as $count) {
            $p = $count / $total;
            $sum += $p * $p;
        }

        return 1.0 - $sum;
    }

    /** Highest count wins; ties go to the alphabetically first class (scikit-learn's class order). */
    private static function majority(array $counts): string
    {
        ksort($counts);
        $best = null;
        foreach ($counts as $label => $count) {
            if ($best === null || $count > $counts[$best]) {
                $best = $label;
            }
        }

        return (string) $best;
    }

    /** Same blend of model and clinical confidence as predict.py's calculate_realistic_confidence(). */
    private static function realisticConfidence(string $risk, int $modelConfidence, float $bmi, float $bloodSugar, float $bloodPressure, float $cholesterol): int
    {
        $points = 0;
        $points += $bmi >= 30 ? 2 : ($bmi >= 25 ? 1 : 0);
        $points += $bloodSugar >= 7.0 ? 2 : ($bloodSugar >= 5.6 ? 1 : 0);
        $points += $bloodPressure >= 140 ? 2 : ($bloodPressure >= 130 ? 1 : 0);
        $points += $cholesterol >= 6.2 ? 2 : ($cholesterol >= 5.2 ? 1 : 0);

        $clinical = match ($risk) {
            'High' => 78 + min($points * 2, 14),
            'Moderate' => 72 + min($points * 2, 14),
            default => $points === 0 ? 82 : 76,
        };

        // Python's round() rounds halves to even; match it so results are identical.
        $blended = self::roundHalfEven($modelConfidence * 0.55 + $clinical * 0.45);

        return max(68, min($blended, 94));
    }

    private static function roundHalfEven(float $value): int
    {
        return (int) round($value, 0, PHP_ROUND_HALF_EVEN);
    }
}
