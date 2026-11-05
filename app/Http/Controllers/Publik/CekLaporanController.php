<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Http\Requests\Publik\CariLaporanRequest;
use App\Models\Laporan;
use App\Support\TurnstileKonfigurasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CekLaporanController extends Controller
{
    private const PESAN = 'Kode laporan tidak ditemukan atau sudah melewati masa pencarian.';

    public function index(): View
    {
        TurnstileKonfigurasi::catat();

        return view('publik.lapor.cek');
    }

    public function cari(CariLaporanRequest $request): View|RedirectResponse|Response
    {
        if ($request->filled('jawaban_cadangan')) return back()->withInput()->withErrors(['kode' => self::PESAN]);
        $laporan = Laporan::dapatDicari()
            ->with(['perumahan', 'kelurahan.kecamatan'])
            ->where('kode', $request->validated('kode'))
            ->first();
        if (! $laporan) return back()->withInput()->withErrors(['kode' => self::PESAN]);
        return response()->view('publik.lapor.status', ['laporan' => $laporan, 'tanpaFoto' => true])
            ->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
