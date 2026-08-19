<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Money is stored as an unsigned integer number of cents and is never floated.
 *
 * The cast keeps the attribute an int on the way in and out; formatting for
 * display is the job of App\Support\Formatter (server) and useGermanFormat
 * (client), which must agree byte for byte.
 *
 * @implements CastsAttributes<int|null, int|string|null>
 */
class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_float($value)) {
            throw new InvalidArgumentException(
                "Attribute [{$key}] must be an integer number of cents, float given."
            );
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException(
                "Attribute [{$key}] must be an integer number of cents."
            );
        }

        return (int) $value;
    }
}
