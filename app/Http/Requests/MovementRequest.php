<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\IndonesianMessages;
use Illuminate\Foundation\Http\FormRequest;

class MovementRequest extends FormRequest
{
    use IndonesianMessages;

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:death,cull,harvest'],
            'quantity' => ['required', 'integer', 'min:1'],
            'movement_date' => ['required', 'date', 'before_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['type' => 'Jenis mutasi', 'quantity' => 'Jumlah', 'movement_date' => 'Tanggal', 'reason' => 'Alasan'];
    }
}
