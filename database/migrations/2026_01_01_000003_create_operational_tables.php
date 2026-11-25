<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tahap 2 — Operasional harian, pakan, bobot, telur, panen. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $t->date('record_date');
            $t->unsignedInteger('deaths')->default(0);
            $t->unsignedInteger('culls')->default(0);
            $t->decimal('temperature', 4, 1)->nullable();
            $t->unsignedTinyInteger('humidity')->nullable();
            $t->string('litter_condition', 30)->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->softDeletes();
            $t->timestamps();
            $t->unique(['batch_id', 'record_date']);
        });

        Schema::create('feed_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $t->date('record_date');
            $t->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $t->string('feed_phase', 30)->nullable();   // starter | grower | finisher | layer
            $t->decimal('feed_kg', 10, 2);
            $t->decimal('cost', 15, 2)->default(0);     // biaya dibebankan ke batch (sekali)
            $t->timestamps();
            $t->index(['batch_id', 'record_date']);
        });

        Schema::create('weight_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $t->date('record_date');
            $t->unsignedInteger('age_days');
            $t->unsignedInteger('sample_count')->nullable();
            $t->decimal('total_sample_weight_g', 12, 2)->nullable();
            $t->decimal('avg_weight_g', 8, 2);
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['batch_id', 'record_date']);
        });

        Schema::create('egg_productions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $t->date('record_date');
            $t->unsignedInteger('total_eggs');
            $t->unsignedInteger('sellable_eggs')->default(0);
            $t->unsignedInteger('cracked_eggs')->default(0);
            $t->unsignedInteger('dirty_eggs')->default(0);
            $t->unsignedInteger('reject_eggs')->default(0);
            $t->decimal('egg_weight_kg', 10, 3)->default(0);
            $t->unsignedInteger('productive_hens');      // penyebut HDP
            $t->timestamps();
            $t->unique(['batch_id', 'record_date']);
        });

        Schema::create('harvests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $t->date('harvest_date');
            $t->unsignedInteger('heads');
            $t->decimal('total_weight_kg', 12, 2);
            $t->string('weight_basis', 10)->default('live'); // live | carcass
            $t->text('notes')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['harvests', 'egg_productions', 'weight_records', 'feed_records', 'daily_records'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
