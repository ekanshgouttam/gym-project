<?php

namespace App\Support;

use App\Models\BodyMeasurement;
use App\Models\BodyScan;

/** Recorded (non-photo) differences between two scans: dates, weight, tape measurements. */
class ScanComparisonFacts
{
    /** Measurements logged within this many days of a scan count as "at" that scan. */
    public const MEASUREMENT_WINDOW_DAYS = 14;

    public static function for(BodyScan $from, BodyScan $to): array
    {
        $before = self::nearestMeasurement($from);
        $after = self::nearestMeasurement($to);

        $measurements = [];
        if ($before && $after && $before->id !== $after->id) {
            foreach (BodyMeasurement::PARTS as $key => $label) {
                $a = $before->{$key . '_cm'};
                $b = $after->{$key . '_cm'};
                if ($a && $b) {
                    $measurements[$label] = ['from' => $a, 'to' => $b, 'delta' => round($b - $a, 1)];
                }
            }
        }

        return [
            'days' => $from->created_at->diffInDays($to->created_at),
            'weight_delta' => ($from->weight_kg && $to->weight_kg) ? round($to->weight_kg - $from->weight_kg, 1) : null,
            'measurements' => $measurements,
            'measured_on' => [optional(optional($before)->measured_on)->toDateString(), optional(optional($after)->measured_on)->toDateString()],
        ];
    }

    private static function nearestMeasurement(BodyScan $scan): ?BodyMeasurement
    {
        $date = $scan->created_at->copy()->startOfDay();

        return BodyMeasurement::where('user_id', $scan->user_id)
            ->whereBetween('measured_on', [$date->copy()->subDays(self::MEASUREMENT_WINDOW_DAYS)->toDateString(), $date->copy()->addDays(self::MEASUREMENT_WINDOW_DAYS)->toDateString()])
            ->orderByRaw('ABS(DATEDIFF(measured_on, ?))', [$date->toDateString()])
            ->first();
    }
}
