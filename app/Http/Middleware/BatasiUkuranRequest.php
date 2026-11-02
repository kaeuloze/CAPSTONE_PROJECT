<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SOP Pengumpulan Capstone poin 4: ukuran request/payload dibatasi (rekomendasi ±10 KB per request;
 * payload besar seperti 10 MB tidak diperbolehkan). Berlaku untuk semua halaman web (disetujui 2 Nov 2026):
 * - isi kiriman (POST/PUT/PATCH/DELETE) maksimal `posyandu.request.maks_kb`;
 * - parameter di alamat (query string) maksimal `posyandu.request.maks_kb`.
 * Form terbesar aplikasi (pemeriksaan lansia, semua isian diisi penuh) ±5,7 KB sehingga pemakaian normal aman.
 *
 * Bila melebihi: kembali ke halaman sebelumnya dengan pesan galat (isian tidak dikembalikan agar data
 * besar tidak ikut tersimpan di sesi); permintaan JSON (polling/presensi) dibalas 413 + pesan.
 */
class BatasiUkuranRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $batas = max(1, (int) config('posyandu.request.maks_kb')) * 1024;

        if ($this->ukuranIsi($request) > $batas || strlen((string) $request->server('QUERY_STRING')) > $batas) {
            $pesan = 'Data yang dikirim terlalu besar (maksimal '.($batas / 1024).' KB). Periksa kembali isian Anda.';

            return $request->expectsJson()
                ? response()->json(['pesan' => $pesan], Response::HTTP_REQUEST_ENTITY_TOO_LARGE)
                : redirect()->to($this->halamanSebelumnya($request))->with('galat', $pesan);
        }

        return $next($request);
    }

    /** Ukuran isi kiriman: nilai terbesar dari header Content-Length, isi mentah, atau isian form. */
    private function ukuranIsi(Request $request): int
    {
        return max(
            (int) $request->server('CONTENT_LENGTH'),
            strlen((string) $request->getContent()),
            strlen(http_build_query($request->request->all())),
        );
    }

    /** Halaman asal (Referer) bila dari situs ini dan bukan alamat yang sama; selain itu beranda. */
    private function halamanSebelumnya(Request $request): string
    {
        $asal = url()->previous();

        return str_starts_with($asal, url('/')) && strlen($asal) <= 2048 && $asal !== $request->fullUrl()
            ? $asal
            : url('/');
    }
}
