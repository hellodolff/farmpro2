<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\IndonesianMessages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CoopRequest extends FormRequest
{
    use IndonesianMessages;

    public function rules(): array
    {
        $coop = $this->route('coop');

        return [
            'code' => ['required', 'alpha_dash', 'max:20', Rule::unique('coops', 'code')->ignore($coop?->id)],
            'name' => ['required', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'type' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', Rule::in(['available', 'maintenance'])], // "occupied" diatur otomatis oleh sistem
            'sanitation_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'Kode', 'name' => 'Nama', 'location' => 'Lokasi', 'capacity' => 'Kapasitas', 'type' => 'Tipe', 'status' => 'Status'];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $coop = $this->route('coop');
            if (! $coop) {
                return;
            }
            $active = $coop->batches()->where('status', 'active');
            $used = (int) (clone $active)->sum('current_population');

            if ($this->filled('capacity') && (int) $this->capacity < $used) {
                $v->errors()->add('capacity', "Kapasitas tidak boleh di bawah populasi aktif saat ini ({$used} ekor).");
            }
            if ($this->status === 'maintenance' && $active->exists()) {
                $v->errors()->add('status', 'Kandang masih dipakai batch aktif, tidak dapat diubah ke perawatan.');
            }
        }];
    }
}
