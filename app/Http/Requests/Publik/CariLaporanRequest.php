<?php

namespace App\Http\Requests\Publik;

use App\Rules\TurnstileTerverifikasi;
use Illuminate\Foundation\Http\FormRequest;
use App\Support\TurnstileKonfigurasi;

class CariLaporanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $kode = $this->input('kode');
        if (is_string($kode)) $this->merge(['kode' => strtoupper(str_replace(' ', '', trim($kode)))]);
    }

    public function rules(): array
    {
        $turnstile = ! TurnstileKonfigurasi::aktif() ? [] : ['required', new TurnstileTerverifikasi];
        return ['kode' => ['bail', 'required', 'string', 'regex:/^[A-Z0-9-]{6,24}$/'],
            'cf-turnstile-response' => $turnstile];
    }

    public function messages(): array
    {
        return ['kode.required' => 'Kode laporan tidak ditemukan atau sudah melewati masa pencarian.',
            'kode.string' => 'Kode laporan tidak ditemukan atau sudah melewati masa pencarian.',
            'kode.regex' => 'Kode laporan tidak ditemukan atau sudah melewati masa pencarian.',
            'cf-turnstile-response.required' => 'Kode laporan tidak ditemukan atau sudah melewati masa pencarian.'];
    }
}
