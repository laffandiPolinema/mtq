<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman peserta ({code}) hanya terbuka bagi sesi yang sudah membuktikan
 * kepemilikan kode, yaitu lewat pendaftaran atau pencocokan kode + nomor WhatsApp.
 */
class EnsureParticipantAccess
{
    public const SESSION_KEY = 'participant_access';

    public function handle(Request $request, Closure $next): Response
    {
        $code = (string) $request->route('code');

        if (! in_array($code, $request->session()->get(self::SESSION_KEY, []), true)) {
            return redirect()
                ->route('status.form', ['kode' => $code])
                ->with('info', 'Masukkan nomor WhatsApp yang didaftarkan untuk membuka data pendaftaran ini.');
        }

        return $next($request);
    }

    public static function grant(Request $request, string $code): void
    {
        $codes = $request->session()->get(self::SESSION_KEY, []);

        if (! in_array($code, $codes, true)) {
            $request->session()->put(self::SESSION_KEY, [...$codes, $code]);
        }
    }
}
