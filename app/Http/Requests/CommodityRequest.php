<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\IndonesianMessages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommodityRequest extends FormRequest
{
    use IndonesianMessages;

    public function rules(): array
    {
        $current = $this->route('commodity');

        return [
            'code' => $current ? ['nullable'] : ['required', 'alpha_dash', 'max:20', Rule::unique('commodities', 'code')],
            'name' => ['required', 'string', 'max:50'],
            'product_unit' => ['required', Rule::in(['kg', 'butir'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'target_fcr_min' => ['nullable', 'numeric', 'between:0,99'],
            'target_fcr_max' => ['nullable', 'numeric', 'between:0,99', 'gte:target_fcr_min'],
            'target_mortality_pct' => ['nullable', 'numeric', 'between:0,100'],
            'target_harvest_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'target_cycle_days' => ['nullable', 'integer', 'min:1'],
            'effective_from' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'Kode', 'name' => 'Nama', 'product_unit' => 'Satuan produksi',
            'target_fcr_min' => 'FCR minimum', 'target_fcr_max' => 'FCR maksimum',
            'target_mortality_pct' => 'Target mortalitas', 'target_harvest_weight_kg' => 'Target bobot panen',
            'target_cycle_days' => 'Durasi siklus', 'effective_from' => 'Tanggal berlaku',
        ];
    }
}
