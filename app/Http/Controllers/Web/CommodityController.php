<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureRole;
use App\Http\Requests\CommodityRequest;
use App\Models\AuditLog;
use App\Models\Commodity;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;

class CommodityController extends Controller implements HasMiddleware
{
    /** Semua peran boleh melihat; hanya admin yang mengubah master komoditas & target. */
    public static function middleware(): array
    {
        return [new Middleware(EnsureRole::class.':admin', except: ['index'])];
    }

    public function index()
    {
        return view('farmpro.commodities.index', ['commodities' => Commodity::withCount('batches')->orderBy('id')->get()]);
    }

    public function create()
    {
        return view('farmpro.commodities.form', ['commodity' => new Commodity(['product_unit' => 'kg'])]);
    }

    public function store(CommodityRequest $r)
    {
        $c = Commodity::create($r->validated());
        AuditLog::record('create', $c, null, ['code' => $c->code]);

        return redirect()->route('commodities.index')->with('success', "Komoditas {$c->name} ditambahkan.");
    }

    public function edit(Commodity $commodity)
    {
        return view('farmpro.commodities.form', compact('commodity'));
    }

    public function update(CommodityRequest $r, Commodity $commodity)
    {
        $commodity->fill(Arr::except($r->validated(), ['code'])); // kode tidak dapat diubah
        $changes = $commodity->getDirty();

        if ($changes) {
            $old = array_intersect_key($commodity->getOriginal(), $changes);
            $commodity->save();
            AuditLog::record('update', $commodity, $old, $changes); // perubahan target tercatat
        }

        return redirect()->route('commodities.index')->with('success', "Komoditas {$commodity->name} diperbarui.");
    }

    public function destroy(Commodity $commodity)
    {
        if ($commodity->batches()->withTrashed()->exists()) {
            return back()->with('error', 'Komoditas sudah dipakai batch dan tidak dapat dihapus.');
        }
        $commodity->delete();

        return redirect()->route('commodities.index')->with('success', 'Komoditas dihapus.');
    }
}
