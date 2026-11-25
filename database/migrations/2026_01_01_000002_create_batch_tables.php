<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Batch = entitas pusat. Populasi berubah hanya lewat mutasi. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();          // kode tidak boleh dipakai ulang (soft delete tetap menjaga unique)
            $t->foreignId('commodity_id')->constrained();
            $t->foreignId('coop_id')->constrained();
            $t->foreignId('supplier_id')->nullable()->constrained('customers_suppliers')->nullOnDelete();
            $t->date('start_date');
            $t->date('end_date')->nullable();
            $t->string('seed_type', 10)->default('doc'); // doc | pullet
            $t->unsignedInteger('initial_population');
            $t->unsignedInteger('current_population');   // di-update hanya oleh PopulationService
            $t->decimal('price_per_head', 12, 2)->default(0);
            $t->decimal('initial_cost', 15, 2)->default(0);
            $t->string('status', 15)->default('active'); // planned | active | completed | cancelled
            $t->text('notes')->nullable();
            $t->softDeletes();
            $t->timestamps();
            $t->index(['status', 'commodity_id']);
        });

        Schema::create('batch_population_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $t->string('type', 15);                    // death | cull | harvest | transfer_in | transfer_out
            $t->date('movement_date');
            $t->unsignedInteger('quantity');
            $t->string('reason')->nullable();
            $t->foreignId('related_batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['batch_id', 'movement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_population_movements');
        Schema::dropIfExists('batches');
    }
};
