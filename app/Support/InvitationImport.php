<?php

namespace App\Support;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * Reads a partner list out of a CSV and says exactly what will happen before
 * anything is sent.
 *
 * A bulk invite is irreversible in the way that matters — you cannot un-send a
 * hundred e-mails — so this always produces a preview first: who will be
 * invited, who is skipped and why. The operator confirms that list, not a file
 * they uploaded and cannot see inside.
 */
class InvitationImport
{
    /** Guards against someone uploading an export of their whole CRM. */
    public const MAX_ROWS = 2000;

    /** Column headings understood for the address, in either language. */
    private const EMAIL_KEYS = ['email', 'e-mail', 'mail', 'e_mail', 'emailadresse', 'e-mail-adresse'];

    private const NAME_KEYS = ['name', 'firma', 'company', 'unternehmen', 'kontakt', 'ansprechpartner'];

    /**
     * @return array{rows: array<int, array<string, mixed>>, counts: array<string, int>, truncated: bool}
     */
    public static function preview(UploadedFile $file): array
    {
        $parsed = self::parse($file);

        $existingUsers = User::whereIn('email', $parsed->pluck('email'))
            ->pluck('email')
            ->map(fn (string $e) => mb_strtolower($e))
            ->all();

        $openInvites = Invitation::whereNull('accepted_at')
            ->whereIn('email', $parsed->pluck('email'))
            ->pluck('email')
            ->map(fn (string $e) => mb_strtolower($e))
            ->all();

        $seen = [];
        $rows = [];

        foreach ($parsed as $entry) {
            $email = $entry['email'];

            $status = match (true) {
                ! self::isValidEmail($email) => 'ungueltig',
                in_array($email, $seen, true) => 'doppelt',
                in_array($email, $existingUsers, true) => 'vorhanden',
                in_array($email, $openInvites, true) => 'eingeladen',
                default => 'neu',
            };

            $seen[] = $email;

            $rows[] = [
                'email' => $entry['email'],
                'name' => $entry['name'],
                'line' => $entry['line'],
                'status' => $status,
                'reason' => self::reasonFor($status),
            ];
        }

        return [
            'rows' => $rows,
            'counts' => [
                'total' => count($rows),
                'neu' => collect($rows)->where('status', 'neu')->count(),
                'vorhanden' => collect($rows)->where('status', 'vorhanden')->count(),
                'eingeladen' => collect($rows)->where('status', 'eingeladen')->count(),
                'doppelt' => collect($rows)->where('status', 'doppelt')->count(),
                'ungueltig' => collect($rows)->where('status', 'ungueltig')->count(),
            ],
            'truncated' => $parsed->count() >= self::MAX_ROWS,
        ];
    }

    /**
     * Reads the file into address/name pairs.
     *
     * Accepts a file with headings or one bare column of addresses, and both
     * comma and semicolon separators — Excel in a German locale writes
     * semicolons, which is what most of these lists will be.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private static function parse(UploadedFile $file): Collection
    {
        $contents = (string) file_get_contents($file->getRealPath());

        // Strip a UTF-8 BOM, which Excel adds and which otherwise corrupts the
        // first heading and makes the address column unfindable.
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];
        $separator = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';

        $headings = null;
        $rows = collect();

        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = array_map(fn (string $c) => trim($c, " \t\"'"), str_getcsv($line, $separator, '"', '\\'));

            // The first non-empty line is a heading row only if it contains no
            // address; a bare list of addresses needs no headings at all.
            if ($headings === null && ! self::rowHasEmail($cells)) {
                $headings = array_map(fn (string $c) => mb_strtolower(trim($c)), $cells);

                continue;
            }

            $entry = self::extract($cells, $headings);

            if ($entry === null) {
                continue;
            }

            $rows->push([...$entry, 'line' => $index + 1]);

            if ($rows->count() >= self::MAX_ROWS) {
                break;
            }
        }

        return $rows;
    }

    /** @param array<int, string> $cells */
    private static function rowHasEmail(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (str_contains($cell, '@')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $cells
     * @param  array<int, string>|null  $headings
     * @return array<string, string>|null
     */
    private static function extract(array $cells, ?array $headings): ?array
    {
        $email = null;
        $name = null;

        if ($headings !== null) {
            foreach ($headings as $position => $heading) {
                $value = $cells[$position] ?? '';

                if ($email === null && in_array($heading, self::EMAIL_KEYS, true)) {
                    $email = $value;
                }

                if ($name === null && in_array($heading, self::NAME_KEYS, true)) {
                    $name = $value;
                }
            }
        }

        // No usable headings, or the heading did not match: fall back to the
        // first cell that looks like an address.
        if (blank($email)) {
            foreach ($cells as $cell) {
                if (str_contains($cell, '@')) {
                    $email = $cell;

                    break;
                }
            }
        }

        if (blank($email)) {
            return null;
        }

        return [
            'email' => mb_strtolower(trim($email)),
            'name' => blank($name) ? null : trim($name),
        ];
    }

    private static function isValidEmail(string $email): bool
    {
        return ! Validator::make(['email' => $email], ['email' => 'required|email:rfc'])->fails();
    }

    private static function reasonFor(string $status): ?string
    {
        return match ($status) {
            'vorhanden' => 'Hat bereits einen Zugang',
            'eingeladen' => 'Offene Einladung besteht bereits',
            'doppelt' => 'Adresse kommt in der Datei mehrfach vor',
            'ungueltig' => 'Keine gültige E-Mail-Adresse',
            default => null,
        };
    }
}
