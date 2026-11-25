<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Server-side matching of face-api.js descriptors (128 floats) against stored patient faces.
 * Used by the kiosk and the pharmacist Quick Scan so stored descriptors never leave the server.
 */
class FaceMatch
{
    /** Max Euclidean distance between descriptors to count as the same person. */
    public const MATCH_THRESHOLD = 0.45;

    /** Minimum gap between the best and second-best patient; smaller means the match is ambiguous. */
    public const AMBIGUITY_MARGIN = 0.05;

    /**
     * Find the closest stored face to $probe among the patients in $query.
     *
     * @param  array<int, float>  $probe
     * @return array{id: int|null, distance: float, second: float, stored: array<int, float>|null}
     */
    public static function closest(Builder $query, array $probe): array
    {
        $best = ['id' => null, 'distance' => INF, 'second' => INF, 'stored' => null];

        $query->whereNotNull('face_descriptor')
            ->where('face_descriptor', '!=', '')
            ->select(['id', 'face_descriptor'])
            ->chunkById(200, function ($patients) use ($probe, &$best) {
                foreach ($patients as $patient) {
                    $stored = self::decode($patient->face_descriptor);
                    if ($stored === null) {
                        continue;
                    }

                    $distance = self::distance($stored, $probe);

                    if ($distance < $best['distance']) {
                        $best['second'] = $best['distance'];
                        $best = ['id' => $patient->id, 'distance' => $distance, 'second' => $best['second'], 'stored' => $stored];
                    } elseif ($distance < $best['second']) {
                        $best['second'] = $distance;
                    }
                }
            });

        return $best;
    }

    /** True when the closest result is a confident, unambiguous match. */
    public static function isConfident(array $result): bool
    {
        return $result['id'] !== null
            && $result['distance'] <= self::MATCH_THRESHOLD
            && $result['second'] - $result['distance'] >= self::AMBIGUITY_MARGIN;
    }

    /** Decode a stored descriptor; null if it is not a valid 128-number array. */
    public static function decode(?string $json): ?array
    {
        $values = json_decode((string) $json, true);

        return self::isValid($values) ? array_map('floatval', array_values($values)) : null;
    }

    /** Whether $values looks like a face-api.js descriptor: exactly 128 numbers in a sane range. */
    public static function isValid(mixed $values): bool
    {
        if (! is_array($values) || count($values) !== 128) {
            return false;
        }

        foreach ($values as $value) {
            if (! is_int($value) && ! is_float($value) && ! is_numeric($value)) {
                return false;
            }
            if ($value < -2 || $value > 2) {
                return false;
            }
        }

        return true;
    }

    /** @param array<int, float> $a @param array<int, float> $b */
    public static function distance(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $value) {
            $diff = $value - $b[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /** @param array<int, array<int, float>> $frames */
    public static function average(array $frames): array
    {
        $out = array_fill(0, 128, 0.0);
        foreach ($frames as $frame) {
            foreach ($frame as $i => $value) {
                $out[$i] += $value;
            }
        }

        return array_map(fn ($v) => $v / count($frames), $out);
    }
}
