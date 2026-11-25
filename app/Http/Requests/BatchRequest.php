<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\IndonesianMessages;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST  = membuat batch (semua kolom).
 * PUT   = hanya catatan & pemasok. Populasi awal, kandang, komoditas, dan kode tidak boleh diedit
 *         agar histori tetap konsisten; koreksi dilakukan lewat mutasi/reversal.
 */
class BatchRequest extends FormRequest
{
    use IndonesianMessages;

    public function rules(): array
    {
        $common = [
            'supplier_id' => ['nullable', 'exists:customers_suppliers,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if (! $this->isMethod('post')) {
            return $common;
        }

        return $common + [
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-]+$/', 'unique:batches,code'], // kode tidak dapat dipakai ulang
            'commodity_id' => ['required', 'exists:commodities,id'],
            'coop_id' => ['required', 'exists:coops,id'],
            'start_date' => ['required', 'date'],
            'seed_type' => ['required', 'in:doc,pullet'],
            'initial_population' => ['required', 'integer', 'min:1'],
            'price_per_head' => ['required', 'numeric', 'min:0'],
            'confirm_over_capacity' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Kode batch', 'commodity_id' => 'Komoditas', 'coop_id' => 'Kandang', 'supplier_id' => 'Pemasok',
            'start_date' => 'Tanggal masuk', 'seed_type' => 'Jenis bibit', 'initial_population' => 'Populasi awal',
            'price_per_head' => 'Harga per ekor', 'notes' => 'Catatan',
        ];
    }
}
