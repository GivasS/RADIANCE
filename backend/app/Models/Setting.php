<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key_name', 'value', 'type', 'description'])]
#[Hidden(['del_flag'])]
class Setting extends Model
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key_name', $key)->first();

            if (! $setting) {
                return $default;
            }

            return match ($setting->type) {
                'int' => (int) $setting->value,
                'decimal' => (float) $setting->value,
                'bool' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
        });
    }
}
