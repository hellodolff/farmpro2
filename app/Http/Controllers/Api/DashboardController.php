<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $r, DashboardService $svc)
    {
        $r->validate(['bulan' => 'nullable|date_format:Y-m']);
        $month = $r->filled('bulan') ? Carbon::createFromFormat('Y-m-d', $r->bulan.'-01')->startOfMonth() : now()->startOfMonth();

        return $svc->summary($month);
    }
}
