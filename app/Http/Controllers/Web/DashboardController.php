<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $r, DashboardService $svc)
    {
        $r->validate(['bulan' => 'nullable|date_format:Y-m']);
        $month = $r->filled('bulan') ? Carbon::createFromFormat('Y-m-d', $r->bulan.'-01')->startOfMonth() : now()->startOfMonth();

        return view('farmpro.dashboard', ['d' => $svc->summary($month), 'month' => $month]);
    }
}
