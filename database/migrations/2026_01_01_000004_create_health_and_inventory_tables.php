<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tahap 3 — Kesehatan & inventaris. Sistem hanya mencatat/mengingatkan, tidak mendiagnosis. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->string('category', 20);                 // feed | vaccine | medicine | vitamin | sanitation | equipment
            $t->string('base_unit', 15);
            $t->string('purchase_unit', 15)->nullable();
            $t->decimal('unit_conversion', 10, 3)->default(1);
            $t->decimal('min_stock', 14, 3)->default(0);
            $t->decimal('stock', 14, 3)->default(0);
            $t->decimal('avg_cost', 15, 2)->default(0); // rata-rata tertimbang
            $t->string('storage_location')->nullable();
            $t->boolean('is_active')->default(true);
            $t->softDeletes();
            $t->timestamps();
            $t->index('category');
        });

        Schema::create('inventory_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $t->string('direction', 3);                 // in | out
            $t->string('reason', 20);                   // purchase | batch_usage | adjustment | expired | return | transfer
            $t->decimal('quantity', 14, 3);
            $t->decimal('unit_cost', 15, 2)->default(0);
            $t->string('lot_number', 50)->nullable();
            $t->date('expiry_date')->nullable();
            $t->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $t->date('movement_date');
            $t->timestamps();
            $t->index(['inventory_item_id', 'movement_date']);
        });

        Schema::create('health_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $t->date('record_date');
            $t->string('type', 15);                     // vaccine | medicine | vitamin | symptom
            $t->string('product_name')->nullable();
            $t->string('dose')->nullable();
            $t->string('method')->nullable();
            $t->string('lot_number', 50)->nullable();
            $t->date('expiry_date')->nullable();
            $t->unsignedSmallInteger('withdrawal_days')->nullable(); // masa henti obat
            $t->decimal('cost', 15, 2)->default(0);
            $t->string('performed_by')->nullable();
            $t->text('symptoms')->nullable();
            $t->string('suspected_disease')->nullable();
            $t->unsignedInteger('cases')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['batch_id', 'record_date']);
        });

        Schema::create('health_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('type', 15)->default('vaccine');
            $t->date('due_date');
            $t->string('status', 10)->default('pending'); // pending | done | skipped
            $t->string('priority', 10)->default('normal');
            $t->foreignId('health_record_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        foreach (['health_schedules', 'health_records', 'inventory_movements', 'inventory_items'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
