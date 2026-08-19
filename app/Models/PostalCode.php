<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostalCode extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'city', 'state'];

    /** The city for a PLZ, or null when the code is not a real German one. */
    public static function cityFor(string $code): ?string
    {
        return static::where('code', $code)->value('city');
    }

    public static function exists(string $code): bool
    {
        return static::where('code', $code)->exists();
    }
}
