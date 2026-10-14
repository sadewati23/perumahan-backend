@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('subtitle', 'Kelola akun administrator dan petugas')

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-3 lg:col-span-2 md:hidden">
            @foreach ($users as $user)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-pill bg-brand-100 text-sm font-semibold text-brand-700">
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
                                    <span class="rounded-pill px-2 py-0.5 font-medium ring-1 ring-inset {{ $user->isAdmin() ? 'bg-brand-50 text-brand-700 ring-brand-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                        {{ $user->label_role }}
                                    </span>
                                    @if ($user->jabatan)
                                        <span class="text-slate-500">{{ $user->jabatan }}</span>
                                    @endif
                                    <span class="rounded-pill px-2 py-0.5 font-medium ring-1 ring-inset {{ $user->is_active ? 'bg-success-50 text-success-700 ring-success-200' : 'bg-danger-50 text-danger-700 ring-danger-200' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <details class="relative">
                                <summary class="cursor-pointer list-none rounded-control border border-slate-300 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                    Ubah
                                </summary>
                                <form method="POST" action="{{ route('users.update', $user) }}"
                                      class="absolute right-0 z-20 mt-2 w-80 max-w-[calc(100vw-3rem)] space-y-2 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                    @csrf
                                    @method('PUT')
                                    @php $popover = true; @endphp
                                    <x-field name="name" for="ubah-{{ $user->id }}-name" label="Nama lengkap" :show-errors="false">
                                        <input id="ubah-{{ $user->id }}-name" type="text" name="name" value="{{ $user->name }}"
                                               required autocomplete="name" class="input">
                                    </x-field>
                                    <x-field name="email" for="ubah-{{ $user->id }}-email" label="Email" :show-errors="false">
                                        <input id="ubah-{{ $user->id }}-email" type="email" name="email" value="{{ $user->email }}"
                                               required autocomplete="email" enterkeyhint="done" class="input">
                                    </x-field>
                                    <x-field name="role" for="ubah-{{ $user->id }}-role" label="Role" :show-errors="false">
                                        <select id="ubah-{{ $user->id }}-role" name="role" class="input">
                                            @foreach (\App\Models\User::ROLE as $value => $label)
                                                <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </x-field>
                                    <x-field name="jabatan" for="ubah-{{ $user->id }}-jabatan" label="Jabatan" optional :show-errors="false">
                                        <input id="ubah-{{ $user->id }}-jabatan" type="text" name="jabatan" value="{{ $user->jabatan }}"
                                               autocomplete="organization-title" class="input">
                                    </x-field>
                                    <x-field name="password" for="ubah-{{ $user->id }}-password" label="Password baru" optional :show-errors="false">
                                        <input id="ubah-{{ $user->id }}-password" type="password" name="password"
                                               autocomplete="new-password" class="input" placeholder="Minimal 6 karakter">
                                    </x-field>
                                    <x-field name="password_confirmation" for="ubah-{{ $user->id }}-password-confirmation" label="Konfirmasi password" optional :show-errors="false">
                                        <input id="ubah-{{ $user->id }}-password-confirmation" type="password" name="password_confirmation"
                                               autocomplete="new-password" class="input">
                                    </x-field>
                                    <label class="flex min-h-11 items-center gap-2 text-xs text-slate-600">
                                        <input type="checkbox" name="is_active" value="1" @checked($user->is_active)
                                               class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        Akun aktif (bisa login)
                                    </label>
                                    <x-button type="submit" class="w-full">Simpan Perubahan</x-button>
                                </form>
                            </details>

                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                      data-confirm="Hapus akun {{ $user->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md px-2 py-2 text-xs font-medium text-danger-600 transition hover:text-danger-700">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Tampilan tabel untuk layar sedang ke atas --}}
            <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Pengguna</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $user)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-800">{{ $user->name }}</p>
                                    @if ($user->jabatan)
                                        <p class="text-xs text-slate-500">{{ $user->jabatan }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :tone="$user->isAdmin() ? 'primary' : 'neutral'" size="sm">{{ $user->label_role }}</x-badge>
                                </td>
                                <td class="px-4 py-3">
                                    <x-badge :tone="$user->is_active ? 'success' : 'danger'" size="sm">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <details class="relative">
                                            <summary class="cursor-pointer list-none rounded-control border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                                Ubah
                                            </summary>
                                            <form method="POST" action="{{ route('users.update', $user) }}"
                                                  class="absolute right-0 z-20 mt-2 w-80 max-w-[calc(100vw-3rem)] space-y-2 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                                @csrf
                                                @method('PUT')
                                                @php $popover = true; @endphp
                                                <x-field name="name" for="ubah-{{ $user->id }}-name" label="Nama lengkap" :show-errors="false">
                                                    <input id="ubah-{{ $user->id }}-name" type="text" name="name" value="{{ $user->name }}"
                                                           required autocomplete="name" class="input">
                                                </x-field>
                                                <x-field name="email" for="ubah-{{ $user->id }}-email" label="Email" :show-errors="false">
                                                    <input id="ubah-{{ $user->id }}-email" type="email" name="email" value="{{ $user->email }}"
                                                           required autocomplete="email" enterkeyhint="done" class="input">
                                                </x-field>
                                                <x-field name="role" for="ubah-{{ $user->id }}-role" label="Role" :show-errors="false">
                                                    <select id="ubah-{{ $user->id }}-role" name="role" class="input">
                                                        @foreach (\App\Models\User::ROLE as $value => $label)
                                                            <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </x-field>
                                                <x-field name="jabatan" for="ubah-{{ $user->id }}-jabatan" label="Jabatan" optional :show-errors="false">
                                                    <input id="ubah-{{ $user->id }}-jabatan" type="text" name="jabatan" value="{{ $user->jabatan }}"
                                                           autocomplete="organization-title" class="input">
                                                </x-field>
                                                <x-field name="password" for="ubah-{{ $user->id }}-password" label="Password baru" optional :show-errors="false">
                                                    <input id="ubah-{{ $user->id }}-password" type="password" name="password"
                                                           autocomplete="new-password" class="input" placeholder="Minimal 6 karakter">
                                                </x-field>
                                                <x-field name="password_confirmation" for="ubah-{{ $user->id }}-password-confirmation" label="Konfirmasi password" optional :show-errors="false">
                                                    <input id="ubah-{{ $user->id }}-password-confirmation" type="password" name="password_confirmation"
                                                           autocomplete="new-password" class="input">
                                                </x-field>
                                                <label class="flex min-h-11 items-center gap-2 text-xs text-slate-600">
                                                    <input type="checkbox" name="is_active" value="1" @checked($user->is_active)
                                                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                    Akun aktif (bisa login)
                                                </label>
                                                <x-button type="submit" class="w-full">Simpan Perubahan</x-button>
                                            </form>
                                        </details>
                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                                  data-confirm="Hapus akun {{ $user->name }}?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-medium text-danger-600 transition hover:text-danger-700">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="hidden md:block">
                {{ $users->links() }}
            </div>
            <div class="md:hidden">
                {{ $users->links() }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-slate-800">Tambah Pengguna</h2>
            <form method="POST" action="{{ route('users.store') }}" class="space-y-3" data-unsaved-guard="Data pengguna belum disimpan.">
                @csrf
                <x-field name="name" label="Nama lengkap" required>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required
                           autocomplete="name" enterkeyhint="next" placeholder="Nama lengkap" class="input">
                </x-field>
                <x-field name="email" label="Email" required>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           autocomplete="email" enterkeyhint="next" placeholder="nama@instansi.go.id" class="input">
                </x-field>
                <x-field name="role" label="Role" required hint="Administrator kelola seluruh data termasuk pengguna; petugas kelola data perumahan & wilayah.">
                    <select id="role" name="role" required class="input">
                        @foreach (\App\Models\User::ROLE as $value => $label)
                            <option value="{{ $value }}" @selected($value === 'petugas')>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field name="jabatan" label="Jabatan" optional>
                    <input id="jabatan" type="text" name="jabatan" value="{{ old('jabatan') }}"
                           autocomplete="organization-title" enterkeyhint="next" placeholder="Jabatan (opsional)" class="input">
                </x-field>
                <x-field name="password" label="Password" required>
                    <input id="password" type="password" name="password" required
                           autocomplete="new-password" enterkeyhint="next" placeholder="Minimal 6 karakter" class="input">
                </x-field>
                <x-field name="password_confirmation" label="Konfirmasi password" required>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           autocomplete="new-password" enterkeyhint="done" placeholder="Ulangi password" class="input">
                </x-field>
                <x-button type="submit" class="w-full">Simpan Pengguna</x-button>
            </form>

            <div class="mt-5 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                <p class="font-semibold text-slate-700">Hak akses</p>
                <p class="mt-1"><span class="font-medium">Administrator</span>: kelola seluruh data termasuk pengguna.</p>
                <p class="mt-1"><span class="font-medium">Petugas</span>: input dan ubah data perumahan serta wilayah.</p>
            </div>
        </div>
    </div>
@endsection
