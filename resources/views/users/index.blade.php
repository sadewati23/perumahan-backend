@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('subtitle', 'Kelola akun administrator dan petugas')

@section('content')
    @php $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20'; @endphp

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-3 lg:col-span-2">
            @foreach ($users as $user)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                            <div>
                                <p class="font-semibold text-slate-800">
                                    {{ $user->name }}
                                    @if ($user->id === auth()->id())
                                        <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-500">Anda</span>
                                    @endif
                                </p>
                                <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                                    <span class="rounded-full px-2 py-0.5 font-medium ring-1 ring-inset {{ $user->isAdmin() ? 'bg-indigo-50 text-indigo-700 ring-indigo-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                        {{ $user->label_role }}
                                    </span>
                                    @if ($user->jabatan)
                                        <span class="text-slate-500">{{ $user->jabatan }}</span>
                                    @endif
                                    <span class="rounded-full px-2 py-0.5 font-medium ring-1 ring-inset {{ $user->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <details class="relative">
                                <summary class="cursor-pointer list-none rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                    Ubah
                                </summary>
                                <form method="POST" action="{{ route('users.update', $user) }}"
                                      class="absolute right-0 z-20 mt-2 w-80 max-w-[calc(100vw-3rem)] space-y-2 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $user->name }}" required placeholder="Nama" class="{{ $inputClass }}">
                                    <input type="email" name="email" value="{{ $user->email }}" required placeholder="Email" class="{{ $inputClass }}">
                                    <select name="role" class="{{ $inputClass }}">
                                        @foreach (\App\Models\User::ROLE as $value => $label)
                                            <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="jabatan" value="{{ $user->jabatan }}" placeholder="Jabatan" class="{{ $inputClass }}">
                                    <input type="password" name="password" placeholder="Password baru (opsional)" class="{{ $inputClass }}">
                                    <input type="password" name="password_confirmation" placeholder="Konfirmasi password" class="{{ $inputClass }}">
                                    <label class="flex items-center gap-2 text-xs text-slate-600">
                                        <input type="checkbox" name="is_active" value="1" @checked($user->is_active)
                                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        Akun aktif (bisa login)
                                    </label>
                                    <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                        Simpan Perubahan
                                    </button>
                                </form>
                            </details>

                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                      data-confirm="Hapus akun {{ $user->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <div>{{ $users->links() }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-slate-800">Tambah Pengguna</h2>
            <form method="POST" action="{{ route('users.store') }}" class="space-y-3">
                @csrf
                <input type="text" name="name" required placeholder="Nama lengkap" class="{{ $inputClass }}">
                <input type="email" name="email" required placeholder="Email" class="{{ $inputClass }}">
                <select name="role" required class="{{ $inputClass }}">
                    @foreach (\App\Models\User::ROLE as $value => $label)
                        <option value="{{ $value }}" @selected($value === 'petugas')>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" name="jabatan" placeholder="Jabatan (opsional)" class="{{ $inputClass }}">
                <input type="password" name="password" required placeholder="Password" class="{{ $inputClass }}">
                <input type="password" name="password_confirmation" required placeholder="Konfirmasi password" class="{{ $inputClass }}">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    Simpan Pengguna
                </button>
            </form>

            <div class="mt-5 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                <p class="font-semibold text-slate-700">Hak akses</p>
                <p class="mt-1"><span class="font-medium">Administrator</span>: kelola seluruh data termasuk pengguna.</p>
                <p class="mt-1"><span class="font-medium">Petugas</span>: input dan ubah data perumahan serta wilayah.</p>
            </div>
        </div>
    </div>
@endsection
