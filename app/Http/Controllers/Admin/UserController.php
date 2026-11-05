<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\Username;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use App\Support\SesiPengguna;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::orderBy('name')->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['username' => is_string($request->input('username')) ? strtolower(trim($request->input('username'))) : $request->input('username')]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', new Username, Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', 'in:admin,petugas'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password.min' => 'Password minimal 12 karakter.',
            'username.unique' => 'Username sudah dipakai.',
        ]);

        User::create($data + ['is_active' => true]);

        return back()->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->merge(['username' => is_string($request->input('username')) ? strtolower(trim($request->input('username'))) : $request->input('username')]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', new Username, Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:admin,petugas'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            'password.min' => 'Password minimal 12 karakter.',
            'username.unique' => 'Username sudah dipakai.',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Cegah admin terakhir menghapus hak aksesnya sendiri.
        if ($user->isAdmin() && $data['role'] !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Tidak bisa mengubah role: minimal harus ada satu administrator.');
        }

        if ($user->id === auth()->id() && ! $data['is_active']) {
            return back()->with('error', 'Anda tidak bisa menonaktifkan akun Anda sendiri.');
        }

        $user->update($data);

        if ((! $data['is_active'] && $user->wasChanged('is_active')) || $user->wasChanged('password')) {
            SesiPengguna::cabut(
                $user,
                $user->id === auth()->id() ? $request->session()->getId() : null,
            );

            if ($user->id === auth()->id() && $user->wasChanged('password')) {
                Auth::logoutOtherDevices($data['password']);
                $request->session()->put('password_hash_web', $user->getAuthPassword());
            }
        }

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Tidak bisa menghapus administrator terakhir.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
