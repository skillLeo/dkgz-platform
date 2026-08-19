<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * The whole configurable layer. Reads go through App\Support\Settings, which
 * caches; this model is the storage shape and the encryption boundary.
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group', 'key', 'value', 'type', 'is_encrypted', 'label_de', 'help_de', 'sort_order',
    ];

    protected $hidden = ['value'];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** The stored string decoded into its declared type. */
    public function typedValue(): mixed
    {
        $raw = $this->rawValue();

        if ($raw === null) {
            return null;
        }

        return match ($this->type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $raw,
            'decimal' => (float) $raw,
            'json' => json_decode($raw, true),
            default => $raw,
        };
    }

    /** Decrypts on the way out when the setting is marked encrypted. */
    public function rawValue(): ?string
    {
        if ($this->value === null || $this->value === '') {
            return $this->value;
        }

        if (! $this->is_encrypted) {
            return $this->value;
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (\Throwable $e) {
            // A key rotation or a hand-edited row must not take the site down.
            Log::warning("Setting [{$this->key}] could not be decrypted.", ['exception' => $e]);

            return null;
        }
    }

    /** Encrypts on the way in when the setting is marked encrypted. */
    public function writeValue(mixed $value): void
    {
        if ($value === null) {
            $this->value = null;

            return;
        }

        $stringValue = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };

        $this->value = $this->is_encrypted
            ? Crypt::encryptString($stringValue)
            : $stringValue;
    }

    /** Secrets are never rendered back into an input — the UI shows a mask. */
    public function isSecret(): bool
    {
        return $this->is_encrypted || $this->type === 'encrypted';
    }

    public function hasValue(): bool
    {
        return $this->value !== null && $this->value !== '';
    }
}
