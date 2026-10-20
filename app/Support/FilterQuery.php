<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class FilterQuery
{
    public static function ambil(Request $request, array $aturan): array
    {
        $validator = Validator::make(
            Arr::only($request->query(), array_keys($aturan)),
            $aturan,
        );

        $validator->passes();
        $gagal = [];
        foreach ($validator->errors()->keys() as $key) {
            $gagal[] = explode('.', $key, 2)[0];
        }

        $data = $validator->getData();
        $hasil = [];
        foreach (array_keys($aturan) as $key) {
            if (in_array($key, $gagal, true) || ! array_key_exists($key, $data) || blank($data[$key])) {
                continue;
            }

            $hasil[$key] = $data[$key];
        }

        return $hasil;
    }

    public static function likeAman(string $kata): string
    {
        return addcslashes($kata, '%_\\');
    }
}
