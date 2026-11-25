<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $r)
    {
        return InventoryItem::where('is_active', true)
            ->when($r->category, fn ($q, $c) => $q->where('category', $c))
            ->orderBy('category')->orderBy('name')->get()
            ->map(fn ($i) => $i->toArray() + ['is_low' => $i->is_low]);
    }

    public function purchase(Request $r, InventoryItem $item, InventoryService $svc)
    {
        $d = $r->validate([
            'quantity' => 'required|numeric|gt:0', 'unit_cost' => 'required|numeric|min:0',
            'movement_date' => 'required|date', 'lot_number' => 'nullable|string|max:50', 'expiry_date' => 'nullable|date',
        ]);

        return response()->json($svc->purchase($item, $d['quantity'], $d['unit_cost'], $d['movement_date'], $d), 201);
    }
}
