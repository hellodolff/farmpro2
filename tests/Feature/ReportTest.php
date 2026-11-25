<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_report_types_render_without_missing_relations(): void
    {
        $user = User::factory()->create();

        foreach (['harian', 'bulanan', 'per-batch', 'populasi', 'produksi', 'inventaris', 'keuangan'] as $jenis) {
            $this->actingAs($user)
                ->get(route('reports.hub', ['jenis' => $jenis]))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('reports.index', ['jenis' => $jenis, 'format' => 'csv']))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('reports.index', ['jenis' => $jenis, 'format' => 'pdf']))
                ->assertOk();
        }
    }
}
