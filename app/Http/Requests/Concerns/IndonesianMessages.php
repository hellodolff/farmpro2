<?php

namespace App\Http\Requests\Concerns;

trait IndonesianMessages
{
    public function authorize(): bool
    {
        return true; // otorisasi peran dilakukan oleh middleware EnsureRole di controller
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah dipakai.',
            'exists' => ':attribute tidak valid.',
            'integer' => ':attribute harus bilangan bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'date' => ':attribute bukan tanggal yang valid.',
            'in' => ':attribute tidak valid.',
            'boolean' => ':attribute tidak valid.',
            'regex' => 'Format :attribute tidak valid.',
            'alpha_dash' => ':attribute hanya boleh huruf, angka, strip, dan garis bawah.',
            'max.string' => ':attribute maksimal :max karakter.',
            'min.numeric' => ':attribute minimal :min.',
            'max.numeric' => ':attribute maksimal :max.',
            'between.numeric' => ':attribute harus antara :min dan :max.',
            'gte.numeric' => ':attribute tidak boleh lebih kecil dari FCR minimum.',
            'before_or_equal' => ':attribute tidak boleh melewati :date.',
        ];
    }
}
