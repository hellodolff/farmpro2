<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tahap 1 — Fondasi: profil peternakan, komoditas, kandang, mitra. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_profiles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('address')->nullable();
            $t->string('timezone', 40)->default('Asia/Jakarta');
            $t->string('currency', 3)->default('IDR');
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('farm_profile_id')->nullable()->constrained()->nullOnDelete();
            $t->string('role', 20)->default('operator'); // admin | operator | viewer
        });

        Schema::create('commodities', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();          // broiler | pejantan | layer
            $t->string('name', 50);
            $t->string('product_unit', 10)->default('kg');
            $t->text('description')->nullable();
            // Target = nilai referensi awal, dapat diubah admin (PDF bagian 20)
            $t->decimal('target_fcr_min', 4, 2)->nullable();
            $t->decimal('target_fcr_max', 4, 2)->nullable();
            $t->decimal('target_mortality_pct', 5, 2)->nullable();
            $t->decimal('target_harvest_weight_kg', 6, 3)->nullable();
            $t->unsignedInteger('target_cycle_days')->nullable();
            $t->date('effective_from')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });

        Schema::create('coops', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name', 80);
            $t->string('location')->nullable();
            $t->unsignedInteger('capacity');
            $t->string('type', 30)->nullable();        // postal, closed house, baterai
            $t->string('status', 20)->default('available'); // available | occupied | maintenance
            $t->text('sanitation_notes')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });

        Schema::create('customers_suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('type', 10);                    // supplier | customer
            $t->string('name');
            $t->string('phone', 30)->nullable();
            $t->string('address')->nullable();
            $t->softDeletes();
            $t->timestamps();
            $t->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers_suppliers');
        Schema::dropIfExists('coops');
        Schema::dropIfExists('commodities');
        Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('farm_profile_id'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
        Schema::dropIfExists('farm_profiles');
    }
};
