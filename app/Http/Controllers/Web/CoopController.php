<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureRole;
use App\Http\Requests\CoopRequest;
use App\Models\AuditLog;
use App\Models\Coop;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CoopController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(EnsureRole::class.':admin', except: ['index'])];
    }

    public function index()
    {
        $coops = Coop::withSum(['batches as used_population' => fn ($q) => $q->where('status', 'active')], 'current_population')
            ->withCount(['batches as active_batches' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('code')->get();

        return view('farmpro.coops.index', compact('coops'));
    }

    public function create()
    {
        return view('farmpro.coops.form', ['coop' => new Coop(['status' => 'available']), 'inUse' => false]);
    }

    public function store(CoopRequest $r)
    {
        $coop = Coop::create($r->validated() + ['status' => 'available']);
        AuditLog::record('create', $coop, null, ['code' => $coop->code]);

        return redirect()->route('coops.index')->with('success', "Kandang {$coop->name} ditambahkan.");
    }

    public function edit(Coop $coop)
    {
        return view('farmpro.coops.form', ['coop' => $coop, 'inUse' => $coop->batches()->where('status', 'active')->exists()]);
    }

    public function update(CoopRequest $r, Coop $coop)
    {
        $data = $r->validated();
        if ($coop->batches()->where('status', 'active')->exists()) {
            unset($data['status']); // status "terisi" dikelola sistem selama ada batch aktif
        }
        $coop->update($data);

        return redirect()->route('coops.index')->with('success', "Kandang {$coop->name} diperbarui.");
    }

    public function destroy(Coop $coop)
    {
        if ($coop->batches()->withTrashed()->exists()) {
            return back()->with('error', 'Kandang memiliki riwayat batch dan tidak dapat dihapus.');
        }
        $coop->delete();

        return redirect()->route('coops.index')->with('success', 'Kandang dihapus.');
    }
}
