<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Optional TOTP for staff accounts. The secret is stored encrypted on the user
 * row; verification is a plain RFC 6238 check with a one-step window either
 * side to tolerate clock drift.
 */
class TwoFactorController extends Controller
{
    public function challenge(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('two_factor_user_id')) {
            return redirect()->route('admin.login');
        }

        return Inertia::render('Auth/ZweiFaktor');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(
            ['code' => ['required', 'string', 'size:6']],
            [],
            ['code' => 'der Bestätigungscode']
        );

        $userId = $request->session()->get('two_factor_user_id');
        abort_if($userId === null, 419);

        $user = User::findOrFail($userId);

        if (! $this->codeMatches($user->two_factor_secret, $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => 'Der Code ist ungültig oder abgelaufen.',
            ]);
        }

        $request->session()->forget('two_factor_user_id');
        auth()->login($user, $request->session()->pull('two_factor_remember', false));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    private function codeMatches(?string $secret, string $code): bool
    {
        if ($secret === null) {
            return false;
        }

        $timestamp = (int) floor(time() / 30);

        // One step either side, so a slightly wrong clock does not lock a
        // legitimate admin out of their own panel.
        foreach ([-1, 0, 1] as $offset) {
            if (hash_equals($this->totp($secret, $timestamp + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    private function totp(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $binary = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binary, $key, true);
        $offset = ord($hash[19]) & 0xF;

        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = rtrim(strtoupper($secret), '=');
        $buffer = 0;
        $bits = 0;
        $output = '';

        foreach (str_split($secret) as $char) {
            $index = strpos($alphabet, $char);

            if ($index === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $index;
            $bits += 5;

            if ($bits >= 8) {
                $bits -= 8;
                $output .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $output;
    }
}
