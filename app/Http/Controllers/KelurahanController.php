<?php

namespace App\Http\Controllers;

use App\Models\Kelurahan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelurahanController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kecamatan_id' => ['required', 'exists:kecamatans,id'],
            'kode' => ['nullable', 'string', 'max:20', 'unique:kelurahans,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
        ]);

        Kelurahan::create($data);

        return back()->with('success', 'Kelurahan berhasil ditambahkan.');
    }

    public function update(Request $request, Kelurahan $kelurahan): RedirectResponse
    {
        $data = $request->validate([
            'kecamatan_id' => ['required', 'exists:kecamatans,id'],
            'kode' => ['nullable', 'string', 'max:20', Rule::unique('kelurahans', 'kode')->ignore($kelurahan->id)],
            'nama' => ['required', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
        ]);

        $kelurahan->update($data);

        return back()->with('success', 'Data kelurahan berhasil diperbarui.');
    }

    public function destroy(Kelurahan $kelurahan): RedirectResponse
    {
        if ($kelurahan->perumahans()->exists()) {
            return back()->with('error', 'Kelurahan tidak bisa dihapus karena masih dipakai pada data perumahan.');
        }

        $kelurahan->delete();

        return back()->with('success', 'Kelurahan berhasil dihapus.');
    }
}
