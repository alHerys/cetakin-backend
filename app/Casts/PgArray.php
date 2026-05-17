<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class PgArray implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (is_null($value)) {
            return [];
        }

        // Convert PostgreSQL array format {"a","b"} to PHP array
        $value = trim($value, '{}');

        if ($value === '') {
            return [];
        }

        return str_getcsv($value, ',', '"');
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (is_null($value) || $value === []) {
            return '{}';
        }

        // Convert PHP array to PostgreSQL array format {"a","b"}
        $escaped = array_map(fn($item) => '"' . addslashes($item) . '"', $value);

        return '{' . implode(',', $escaped) . '}';
    }
}
