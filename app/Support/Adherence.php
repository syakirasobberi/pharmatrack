<?php

namespace App\Support;

use App\Models\Medication;
use App\Models\MedicationDose;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Medication adherence from the dose log (medication_doses).
 *
 * Adherence = days a dose was logged ÷ days a dose was expected, over the last 30 days.
 *  - Counting starts the day the patient (or pharmacist/kiosk) first logged any dose, so
 *    patients who have never used the log are "Not tracked" instead of 0%.
 *  - It also never starts before the medicine's own start date.
 *  - Today only counts once a dose is logged (the day is not over yet).
 *  - Weekly medicines expect one dose per 7 days; "as needed" (PRN) medicines are left out.
 * 80% or more is the usual threshold for good adherence.
 */
class Adherence
{
    public const WINDOW_DAYS = 30;
    public const GOOD = 80;
    public const FAIR = 50;

    /**
     * Adherence for a patient's current medications.
     *
     * @return array{percent: ?int, status: string, label: string, tracking_since: ?Carbon, medications: array<int, array>}
     */
    public static function forPatient(Patient $patient, ?Collection $currentMeds = null, Carbon|false|null $trackingSince = false): array
    {
        $meds = $currentMeds ?? $patient->currentMedications()->get();
        $meds->loadMissing(['doses' => fn ($q) => $q->whereDate('taken_on', '>=', self::windowStart())]);

        // false = look it up; callers checking many patients pass it from trackingSinceMany().
        $trackingSince = $trackingSince === false ? self::trackingSince($patient) : $trackingSince;

        $results = [];
        $taken = $expected = 0;
        foreach ($meds as $med) {
            $results[$med->id] = $r = self::forMedication($med, $trackingSince);
            if ($r['percent'] !== null) {
                $taken += $r['taken'];
                $expected += $r['expected'];
            }
        }

        $percent = $expected > 0 ? min(100, (int) round($taken / $expected * 100)) : null;

        return [
            'percent' => $percent,
            'status' => $status = self::status($percent, $trackingSince, $meds->isEmpty()),
            'label' => self::label($status),
            'tracking_since' => $trackingSince,
            'medications' => $results,
        ];
    }

    /**
     * @return array{percent: ?int, taken: int, expected: int, status: string, label: string, schedule: string, last7: array}
     */
    public static function forMedication(Medication $med, ?Carbon $trackingSince): array
    {
        $today = Carbon::today();
        $doses = $med->relationLoaded('doses')
            ? $med->doses
            : $med->doses()->whereDate('taken_on', '>=', self::windowStart())->get();
        $doseDays = $doses->map(fn ($d) => $d->taken_on->toDateString())->unique();

        $last7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $last7[] = ['date' => $day, 'taken' => $doseDays->contains($day->toDateString())];
        }

        $schedule = $med->schedule();
        $base = ['taken' => 0, 'expected' => 0, 'schedule' => $schedule, 'last7' => $last7, 'taken_today' => $doseDays->contains($today->toDateString())];

        if ($schedule === 'as_needed') {
            return $base + ['percent' => null, 'status' => 'as_needed', 'label' => self::label('as_needed')];
        }
        if ($trackingSince === null) {
            return $base + ['percent' => null, 'status' => 'not_tracked', 'label' => self::label('not_tracked')];
        }

        // Window: the latest of (30 days ago, first log ever, medicine start) → today.
        $start = collect([
            self::windowStart(),
            $trackingSince->copy()->startOfDay(),
            ($med->start_date ?? $med->created_at)?->copy()->startOfDay(),
        ])->filter()->max();

        $taken = $doseDays->filter(fn ($d) => $d >= $start->toDateString())->count();
        $daysBeforeToday = $start->lt($today) ? (int) $start->diffInDays($today) : 0;

        if ($schedule === 'weekly') {
            // One dose per full week; a dose logged in a week not yet complete still counts.
            $expected = max(intdiv($daysBeforeToday + 1, 7), min($taken, 1));
            $taken = min($taken, $expected);
        } else {
            $expected = $daysBeforeToday + ($base['taken_today'] ? 1 : 0);
        }

        $percent = $expected > 0 ? min(100, (int) round($taken / $expected * 100)) : null;
        $status = $percent === null ? 'new' : self::status($percent, $trackingSince, false);

        return array_merge($base, [
            'taken' => $taken,
            'expected' => $expected,
            'percent' => $percent,
            'status' => $status,
            'label' => self::label($status),
        ]);
    }

    /** The first day any dose was logged for this patient, or null if never. */
    public static function trackingSince(Patient $patient): ?Carbon
    {
        $first = MedicationDose::whereIn('medication_id', $patient->medications()->select('id'))->min('taken_on');

        return $first ? Carbon::parse($first) : null;
    }

    /** trackingSince() for many patients in one query: [patient_id => Carbon]. */
    public static function trackingSinceMany(iterable $patientIds): array
    {
        return MedicationDose::query()
            ->join('medications', 'medications.id', '=', 'medication_doses.medication_id')
            ->whereIn('medications.patient_id', collect($patientIds)->all())
            ->groupBy('medications.patient_id')
            ->selectRaw('medications.patient_id, MIN(medication_doses.taken_on) as first_dose')
            ->pluck('first_dose', 'patient_id')
            ->map(fn ($d) => Carbon::parse($d))
            ->all();
    }

    public static function windowStart(): Carbon
    {
        return Carbon::today()->subDays(self::WINDOW_DAYS - 1);
    }

    /**
     * Tailwind classes for a status, written out in full so Tailwind's scanner keeps them
     * (app/Support is in tailwind.config.js content).
     *
     * @return array{text: string, bar: string, border: string, badge: string}
     */
    public static function classes(string $status): array
    {
        return match ($status) {
            'good' => ['text' => 'text-emerald-700', 'bar' => 'bg-emerald-500', 'border' => 'border-emerald-200', 'badge' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
            'fair' => ['text' => 'text-amber-700', 'bar' => 'bg-amber-500', 'border' => 'border-amber-200', 'badge' => 'bg-amber-100 text-amber-700 border-amber-200'],
            'poor' => ['text' => 'text-red-700', 'bar' => 'bg-red-500', 'border' => 'border-red-200', 'badge' => 'bg-red-100 text-red-700 border-red-200'],
            default => ['text' => 'text-slate-600', 'bar' => 'bg-slate-400', 'border' => 'border-slate-200', 'badge' => 'bg-slate-100 text-slate-600 border-slate-200'],
        };
    }

    private static function status(?int $percent, ?Carbon $trackingSince, bool $noMeds): string
    {
        if ($noMeds) {
            return 'no_meds';
        }
        if ($trackingSince === null) {
            return 'not_tracked';
        }
        if ($percent === null) {
            return 'new';
        }

        return $percent >= self::GOOD ? 'good' : ($percent >= self::FAIR ? 'fair' : 'poor');
    }

    private static function label(string $status): string
    {
        return [
            'good' => 'Good',
            'fair' => 'Needs support',
            'poor' => 'Poor',
            'new' => 'Just started',
            'not_tracked' => 'Not tracked yet',
            'as_needed' => 'As needed',
            'no_meds' => 'No medications',
        ][$status] ?? $status;
    }
}
