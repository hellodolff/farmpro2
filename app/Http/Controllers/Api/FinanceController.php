<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function index(Request $r)
    {
        $r->validate(['bulan' => 'nullable|date_format:Y-m']);
        $month = $r->bulan ?? now()->format('Y-m');

        return FinancialTransaction::where('trx_date', 'like', $month.'%')->latest('trx_date')->latest('id')->get();
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'trx_date' => 'required|date', 'type' => 'required|in:income,expense',
            'category' => 'required|string|max:40', 'amount' => 'required|numeric|gt:0',
            'batch_id' => 'nullable|exists:batches,id', 'payment_method' => 'nullable|string|max:20', 'description' => 'nullable|string',
        ]);

        return response()->json(FinancialTransaction::create($d), 201);
    }
}
