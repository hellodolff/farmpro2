<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tahap 4 — Keuangan. Uang selalu DECIMAL, tidak pernah FLOAT. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('harvest_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained('customers_suppliers')->nullOnDelete();
            $t->date('sale_date');
            $t->string('product', 10);                  // meat | egg | manure | other
            $t->decimal('quantity', 14, 3);
            $t->string('unit', 10);
            $t->decimal('price_per_unit', 15, 2);
            $t->decimal('deductions', 15, 2)->default(0);
            $t->decimal('transport_cost', 15, 2)->default(0);
            $t->decimal('total', 15, 2);
            $t->string('payment_status', 10)->default('paid'); // paid | partial | unpaid
            $t->softDeletes();
            $t->timestamps();
        });

        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained('customers_suppliers')->nullOnDelete();
            $t->date('expense_date');
            $t->string('category', 30);
            $t->decimal('amount', 15, 2);
            $t->string('payment_method', 20)->nullable();
            $t->string('receipt_path')->nullable();
            $t->text('notes')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });

        // Sumber tunggal untuk dashboard & laporan keuangan.
        Schema::create('financial_transactions', function (Blueprint $t) {
            $t->id();
            $t->date('trx_date');
            $t->string('type', 10);                     // income | expense | transfer (transfer tidak masuk laba-rugi)
            $t->string('category', 40);
            $t->decimal('amount', 15, 2);
            $t->string('payment_method', 20)->nullable();
            $t->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $t->nullableMorphs('source');               // jejak ke transaksi sumber
            $t->text('description')->nullable();
            $t->foreignId('reversed_of_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
            $t->timestamps();
            $t->index(['type', 'trx_date']);
        });

        Schema::create('budget_simulations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->foreignId('commodity_id')->nullable()->constrained()->nullOnDelete();
            $t->json('inputs');
            $t->json('results');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        // Dinamai farm_notifications agar tidak bentrok dengan tabel `notifications` bawaan Laravel.
        Schema::create('farm_notifications', function (Blueprint $t) {
            $t->id();
            $t->string('type', 30);                     // vaccine_due | low_stock | high_mortality | hdp_drop | harvest
            $t->string('priority', 10)->default('normal');
            $t->string('title');
            $t->text('body')->nullable();
            $t->nullableMorphs('subject');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 30);
            $t->nullableMorphs('auditable');
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'farm_notifications', 'budget_simulations', 'financial_transactions', 'expenses', 'sales'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
