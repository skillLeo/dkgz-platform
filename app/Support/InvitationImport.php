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

    /**
     * An address as it appears in a pasted list.
     *
     * Deliberately permissive about what surrounds it — angle brackets, quoted
     * display names, stray semicolons — and strict only about the shape of the
     * address itself. Anything it lets through is validated properly afterwards
     * and shown in the preview before a single message is sent.
     */
    private const EMAIL_PATTERN = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';

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

            foreach (self::extract($cells, $headings, $line) as $entry) {
                $rows->push([...$entry, 'line' => $index + 1]);

                if ($rows->count() >= self::MAX_ROWS) {
                    break 2;
                }
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
     * Every address on one line, with a name where the file gives one.
     *
     * A row may be a record — `email;name` under headings — or it may simply be
     * a run of addresses somebody pasted separated by semicolons. This used to
     * take the first cell containing an "@" and drop the rest, so pasting fifty
     * addresses on one line invited exactly one person and silently discarded
     * forty-nine.
     *
     * @param  array<int, string>  $cells
     * @param  array<int, string>|null  $headings
     * @return list<array<string, string|null>>
     */
    private static function extract(array $cells, ?array $headings, string $line): array
    {
        // Headings name the address column, so the row is one record and the
        // other cells belong to it.
        if ($headings !== null) {
            $email = null;
            $name = null;

            foreach ($headings as $position => $heading) {
                $value = $cells[$position] ?? '';

                if ($email === null && in_array($heading, self::EMAIL_KEYS, true)) {
                    $email = $value;
                }

                if ($name === null && in_array($heading, self::NAME_KEYS, true)) {
                    $name = $value;
                }
            }

            if (filled($email)) {
                return [[
                    'email' => mb_strtolower(trim($email)),
                    'name' => blank($name) ? null : trim($name),
                ]];
            }
        }

        // Otherwise read the addresses straight out of the line rather than
        // trusting it to be well-formed CSV. People paste from Outlook, which
        // writes `"Müller, Jan" <jan@x.de>; anna@y.de`, and from spreadsheets
        // that mix commas and semicolons in the same row. Looking for the
        // addresses themselves survives all of it; splitting on a separator
        // chosen from the first line does not.
        preg_match_all(self::EMAIL_PATTERN, $line, $matches);

        $found = collect($matches[0])
            ->map(fn (string $email) => ['email' => mb_strtolower(trim($email)), 'name' => null])
            ->all();

        // A token with an "@" that did not parse is a typo, not a blank line.
        // It is kept so the preview can show it as unusable — an address that
        // silently disappears is one the operator never learns to correct.
        foreach (preg_split('/[;,\s]+/', $line) ?: [] as $token) {
            $token = trim($token, " \t\"'<>");

            if (! str_contains($token, '@')) {
                continue;
            }

            foreach ($matches[0] as $valid) {
                if (str_contains($token, $valid)) {
                    continue 2;
                }
            }

            $found[] = ['email' => mb_strtolower($token), 'name' => null];
        }

        return $found;
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
