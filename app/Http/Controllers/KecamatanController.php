<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\Perumahan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KecamatanController extends Controller
{
    public function index(): View
    {
        $kecamatans = Kecamatan::with(['kelurahans' => fn ($q) => $q->orderBy('nama')])
            ->withCount('kelurahans')
            ->orderBy('nama')
            ->get();

        return view('wilayah.index', [
            'kecamatans' => $kecamatans,
            'totalKelurahan' => $kecamatans->sum('kelurahans_count'),
            'totalPerumahan' => Perumahan::count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:20', 'unique:kecamatans,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'kabupaten_kota' => ['nullable', 'string', 'max:100'],
        ]);

        Kecamatan::create($data);

        return back()->with('success', 'Kecamatan berhasil ditambahkan.');
    }

    public function update(Request $request, Kecamatan $kecamatan): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['nullable', 'string', 'max:20', Rule::unique('kecamatans', 'kode')->ignore($kecamatan->id)],
            'nama' => ['required', 'string', 'max:100'],
            'kabupaten_kota' => ['nullable', 'string', 'max:100'],
        ]);

        $kecamatan->update($data);

        return back()->with('success', 'Data kecamatan berhasil diperbarui.');
    }

    public function destroy(Kecamatan $kecamatan): RedirectResponse
    {
        if ($kecamatan->perumahans()->exists()) {
            return back()->with('error', 'Kecamatan tidak bisa dihapus karena masih memiliki data perumahan.');
        }

        $kecamatan->delete();

        return back()->with('success', 'Kecamatan berhasil dihapus.');
    }
}
