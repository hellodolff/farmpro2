<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false; // tabel hanya punya created_at (default DB)

    protected $guarded = [];

    /** Catat perubahan penting (PDF bagian 17: audit log koreksi populasi, stok, transaksi, batch). */
    public static function record(string $action, Model $model, ?array $old = null, ?array $new = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => $new ? json_encode($new) : null,
        ]);
    }
}
