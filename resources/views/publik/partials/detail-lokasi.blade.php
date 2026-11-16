<section id="lokasi" class="scroll-mt-28 rounded-card border border-garis bg-white p-5 shadow-card sm:p-6 md:scroll-mt-36">
    <h2 class="font-judul text-xl font-semibold tracking-tight text-tinta">Lokasi Administratif</h2>
    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <dt class="text-xs text-slate-600">Kelurahan</dt>
            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->kelurahan->nama ?? '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs text-slate-600">Kecamatan</dt>
            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->kelurahan->kecamatan->nama ?? '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs text-slate-600">Kabupaten / Kota</dt>
            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->kelurahan->kecamatan->kabupaten_kota ?? '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs text-slate-600">RT / RW</dt>
            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->rt ?: '-' }} / {{ $perumahan->rw ?: '-' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs text-slate-600">Alamat</dt>
            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->alamat ?: '-' }}</dd>
        </div>
    </dl>
</section>
