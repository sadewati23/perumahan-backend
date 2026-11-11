<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportPerumahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sudah dilindungi middleware 'auth' pada route (admin & petugas).
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File impor wajib diunggah.',
            'file.mimes' => 'File harus berformat CSV, XLS, atau XLSX.',
            'file.max' => 'Ukuran file maksimal 4 MB.',
        ];
    }
}
