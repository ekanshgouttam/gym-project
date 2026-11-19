<?php

namespace App\Support;

use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Reads and checks a member CSV before anything is saved.
 * Columns (header row required, any order, case-insensitive):
 *   name*, email*, phone*, date_of_birth, gender, plan, paid
 * Each row comes back with either clean data or a list of problems.
 */
class MemberCsvImport
{
    public const MAX_ROWS = 1000;
    public const COLUMNS = ['name', 'email', 'phone', 'date_of_birth', 'gender', 'plan', 'paid'];

    /** @return array{rows: array<int, array{line:int, data:array, errors:string[]}>, error: ?string} */
    public static function parse(string $path, Gym $gym): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return ['rows' => [], 'error' => 'The file could not be read.'];
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return ['rows' => [], 'error' => 'The file is empty.'];
        }
        $header = array_map(function ($h) {
            $h = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)));

            return str_replace([' ', '-'], '_', $h);
        }, $header);

        $missing = array_diff(['name', 'email', 'phone'], $header);
        if ($missing) {
            fclose($handle);

            return ['rows' => [], 'error' => 'Missing column(s): ' . implode(', ', $missing) . '. Download the template to see the format.'];
        }

        $plans = MembershipPlan::availableAt($gym->id)->get()->keyBy(function ($p) { return strtolower(trim($p->name)); });
        $rows = [];
        $seenEmail = [];
        $seenPhone = [];
        $line = 1;

        while (($cells = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($cells, function ($c) { return trim((string) $c) !== ''; })) === 0) {
                continue; // blank line
            }
            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);

                return ['rows' => [], 'error' => 'Please import at most ' . self::MAX_ROWS . ' members per file.'];
            }

            $raw = [];
            foreach ($header as $i => $col) {
                $raw[$col] = trim((string) ($cells[$i] ?? ''));
            }
            $rows[] = self::checkRow($line, $raw, $plans, $seenEmail, $seenPhone);
        }
        fclose($handle);

        if (! $rows) {
            return ['rows' => [], 'error' => 'No member rows found under the header.'];
        }

        // One query each for clashes with existing accounts.
        $emails = User::whereIn('email', array_keys($seenEmail))->pluck('email')->map(function ($e) { return strtolower($e); })->flip();
        $phones = User::whereIn('phone', array_keys($seenPhone))->pluck('phone')->flip();
        foreach ($rows as &$row) {
            if (isset($row['data']['email']) && isset($emails[strtolower($row['data']['email'])])) {
                $row['errors'][] = 'Email already has an account';
            }
            if (isset($row['data']['phone']) && isset($phones[$row['data']['phone']])) {
                $row['errors'][] = 'Mobile number already registered';
            }
        }

        return ['rows' => $rows, 'error' => null];
    }

    private static function checkRow(int $line, array $raw, $plans, array &$seenEmail, array &$seenPhone): array
    {
        $errors = [];
        $data = ['name' => $raw['name'] ?? '', 'email' => strtolower($raw['email'] ?? '')];

        if (Validator::make($data, ['name' => 'required|max:255'])->fails()) {
            $errors[] = 'Name is required';
        }
        if (Validator::make($data, ['email' => 'required|email|max:255'])->fails()) {
            $errors[] = 'Email is missing or invalid';
        } elseif (isset($seenEmail[$data['email']])) {
            $errors[] = 'Same email appears twice in the file';
        } else {
            $seenEmail[$data['email']] = true;
        }

        $phone = OtpService::normalizePhone($raw['phone'] ?? '');
        if (! $phone) {
            $errors[] = 'Mobile must be a valid 10-digit Indian number';
        } elseif (isset($seenPhone[$phone])) {
            $errors[] = 'Same mobile appears twice in the file';
        } else {
            $seenPhone[$phone] = true;
            $data['phone'] = $phone;
        }

        if (($raw['date_of_birth'] ?? '') !== '') {
            $dob = self::date($raw['date_of_birth']);
            if (! $dob || $dob->isFuture() || $dob->lt(now()->subYears(100))) {
                $errors[] = 'Date of birth not understood (use YYYY-MM-DD or DD/MM/YYYY)';
            } else {
                $data['date_of_birth'] = $dob->toDateString();
            }
        }

        if (($raw['gender'] ?? '') !== '') {
            $g = strtolower($raw['gender']);
            $map = ['m' => 'male', 'male' => 'male', 'f' => 'female', 'female' => 'female', 'o' => 'other', 'other' => 'other'];
            if (isset($map[$g])) {
                $data['gender'] = $map[$g];
            } else {
                $errors[] = 'Gender must be male, female or other';
            }
        }

        if (($raw['plan'] ?? '') !== '') {
            $plan = $plans[strtolower($raw['plan'])] ?? null;
            if ($plan) {
                $data['plan_id'] = $plan->id;
                $data['plan_name'] = $plan->name;
            } else {
                $errors[] = 'Plan "' . $raw['plan'] . '" is not offered at this gym';
            }
        }

        $data['paid'] = in_array(strtolower($raw['paid'] ?? ''), ['yes', 'y', '1', 'true', 'paid'], true);

        return ['line' => $line, 'data' => $data, 'errors' => $errors];
    }

    private static function date(string $value): ?Carbon
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            try {
                $d = Carbon::createFromFormat('!' . $format, $value);
                if ($d && $d->format($format) === $value) {
                    return $d;
                }
            } catch (\Throwable $e) {
            }
        }

        return null;
    }
}
