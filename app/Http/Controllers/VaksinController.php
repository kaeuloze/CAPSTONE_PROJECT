<?php

namespace App\Http\Controllers;

use App\Exceptions\DataMasihDipakaiException;
use App\Exceptions\DataSudahBerubahException;
use App\Http\Requests\Vaksin\SimpanVaksinRequest;
use App\Models\Vaksin;
use App\Services\VaksinService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Master Vaksin (Figma 192:320 "Master Vaksin — Kelola Jenis Vaksin", popup 208:894). */
class VaksinController extends Controller
{
    public function __construct(private readonly VaksinService $layanan)
    {
    }

    public function index(Request $request): View
    {
        $filter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return view('vaksin.index', [
            'daftarVaksin' => $this->layanan->daftar($filter['cari'] ?? null),
            'cari' => $filter['cari'] ?? '',
        ]);
    }

    public function store(SimpanVaksinRequest $request): RedirectResponse
    {
        try {
            $vaksin = $this->layanan->tambah($request->dataVaksin());
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->withErrors(['nama_vaksin' => 'Nama vaksin telah ada di daftar.']);
        }

        return redirect()->route('vaksin.index')->with('sukses', "Vaksin {$vaksin->nama_vaksin} berhasil ditambahkan.");
    }

    public function update(SimpanVaksinRequest $request, Vaksin $vaksin): RedirectResponse
    {
        try {
            $vaksin = $this->layanan->ubah($vaksin, $request->dataVaksin(), $request->validated('versi'));
        } catch (DataSudahBerubahException $e) {
            return back()->with('galat', $e->getMessage());
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->withErrors(['nama_vaksin' => 'Nama vaksin telah ada di daftar.']);
        }

        return redirect()->route('vaksin.index', $request->only('cari', 'page'))
            ->with('sukses', "Perubahan vaksin {$vaksin->nama_vaksin} berhasil disimpan.");
    }

    public function destroy(Vaksin $vaksin): RedirectResponse
    {
        try {
            $this->layanan->hapus($vaksin);
        } catch (DataMasihDipakaiException $e) {
            return back()->with('galat', $e->getMessage());
        }

        return redirect()->route('vaksin.index')->with('sukses', "Vaksin {$vaksin->nama_vaksin} berhasil dihapus.");
    }
}
