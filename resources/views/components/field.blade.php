{{-- Komponen field form: label, tanda wajib, hint, dan pesan error per-field.
    Pemakaian:
    <x-field name="email" label="Email" required hint="Email aktif untuk notifikasi.">
        <input id="email" name="email" type="email" class="input" ...>
    </x-field>

    - `name`     : key validasi (pakai titik untuk array, mis. "psu.0.jenis").
    - `for`      : id kontrol yang ditunjuk label; default sama dengan `name`.
    - `label`    : kosong = tanpa label (mis. di dalam tabel baris dinamis).
    - `required` : tanda * merah + teks "wajib diisi" untuk pembaca layar.
    - `showErrors`: false untuk form yang tidak ingin pesan error per-field
                    (hindari error tampil dobel di dua form dalam satu halaman).
    Aria-invalid/aria-describedby pada kontrol di-wire oleh resources/js/form-fields.js.
--}}
@props([
    'name' => '',
    'label' => null,
    'for' => null,
    'required' => false,
    'optional' => false,
    'hint' => null,
    'showErrors' => true,
    'errorText' => null,
])

@php
    $controlId = $for ?? $name;
    $adaError = $showErrors && ($errorText !== null || $errors->has($name));
    $pesanError = $errorText ?? $errors->first($name);
    $slug = preg_replace('/[^A-Za-z0-9_-]+/', '-', $name);
    $hintId = $hint ? ($slug.'-petunjuk') : null;
    $errorId = $adaError ? ($slug.'-pesan-error') : null;
@endphp

<div {{ $attributes->merge(['class' => 'field space-y-1.5']) }} data-field="{{ $name }}"
     @if ($adaError) data-invalid="true" @endif>
    @if ($label)
        <label for="{{ $controlId }}" class="label">
            {{ $label }}
            @if ($required)
                <span class="text-danger-600" aria-hidden="true">*</span><span class="sr-only">(wajib diisi)</span>
            @endif
            @if ($optional)
                <span class="font-normal text-slate-500">(opsional)</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p id="{{ $hintId }}" class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @if ($adaError)
        <p id="{{ $errorId }}" class="text-xs font-medium text-danger-600">{{ $pesanError }}</p>
    @endif
</div>
