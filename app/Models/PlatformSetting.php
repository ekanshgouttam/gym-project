<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Platform-wide settings edited by the Super Admin. Read with PlatformSetting::get('key').
 * DEFAULTS lists every known setting, its type and its default.
 */
class PlatformSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'allow_gym_registration' => ['type' => 'bool', 'value' => true],
        'trial_days' => ['type' => 'int', 'value' => 14],
        'default_plan' => ['type' => 'string', 'value' => 'starter'],
        'support_email' => ['type' => 'string', 'value' => ''],
        'ai_scans_enabled' => ['type' => 'bool', 'value' => true],
        'ai_monthly_budget_usd' => ['type' => 'float', 'value' => 100],
        'usd_to_inr' => ['type' => 'float', 'value' => 84],
    ];

    private const CACHE_KEY = 'platform_settings';

    /** @return array<string, mixed> every known setting with its stored value or default */
    public static function values(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->pluck('value', 'key')->all();
        });

        $out = [];
        foreach (self::DEFAULTS as $key => $def) {
            $out[$key] = array_key_exists($key, $stored) ? self::cast($stored[$key], $def['type']) : $def['value'];
        }

        return $out;
    }

    public static function get(string $key)
    {
        return static::values()[$key] ?? null;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! isset(self::DEFAULTS[$key])) {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        Cache::forget(self::CACHE_KEY);
    }

    private static function cast($value, string $type)
    {
        switch ($type) {
            case 'bool':
                return (bool) (int) $value;
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            default:
                return (string) $value;
        }
    }
}
