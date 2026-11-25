<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Commodity;
use App\Models\Coop;
use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tahap 1: hak akses peran, CRUD master, aturan batch (kapasitas, DOC sekali, reversal, mutasi). */
class FoundationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $role])->save();

        return $u;
    }

    private function master(): array
    {
        return [Commodity::create(['code' => 'broiler', 'name' => 'Broiler']), Coop::create(['code' => 'K1', 'name' => 'Kandang 1', 'capacity' => 100])];
    }

    private function payload(Commodity $c, Coop $k, array $o = []): array
    {
        return $o + ['code' => 'BR-T1', 'commodity_id' => $c->id, 'coop_id' => $k->id, 'start_date' => now()->subDays(3)->toDateString(),
            'seed_type' => 'doc', 'initial_population' => 80, 'price_per_head' => 7000];
    }

    public function test_only_admin_can_manage_commodities(): void
    {
        $data = ['code' => 'pejantan', 'name' => 'Pejantan', 'product_unit' => 'kg'];

        $this->actingAs($this->user('operator'))->post(route('commodities.store'), $data)->assertForbidden();
        $this->actingAs($this->user('admin'))->post(route('commodities.store'), $data)->assertRedirect(route('commodities.index'));
        $this->assertDatabaseHas('commodities', ['code' => 'pejantan']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'create']);
    }

    public function test_viewer_can_read_but_not_create_batch(): void
    {
        [$c, $k] = $this->master();
        $viewer = $this->user('viewer');

        $this->actingAs($viewer)->get(route('batches.index'))->assertOk();
        $this->actingAs($viewer)->post(route('batches.store'), $this->payload($c, $k))->assertForbidden();
    }

    public function test_batch_creation_records_doc_cost_once_and_marks_coop_occupied(): void
    {
        [$c, $k] = $this->master();

        $this->actingAs($this->user('operator'))->post(route('batches.store'), $this->payload($c, $k))->assertRedirect();

        $b = Batch::firstWhere('code', 'BR-T1');
        $this->assertSame(80, $b->current_population);
        $this->assertSame(1, FinancialTransaction::where('batch_id', $b->id)->count());
        $this->assertEquals(560000, FinancialTransaction::sum('amount'));
        $this->assertSame('occupied', $k->fresh()->status);
    }

    public function test_over_capacity_needs_confirmation(): void
    {
        [$c, $k] = $this->master();
        $op = $this->user('operator');
        $over = $this->payload($c, $k, ['initial_population' => 150]);

        $this->actingAs($op)->post(route('batches.store'), $over)->assertSessionHas('capacity_warning');
        $this->assertDatabaseCount('batches', 0);

        $this->actingAs($op)->post(route('batches.store'), $over + ['confirm_over_capacity' => 1])->assertRedirect();
        $this->assertDatabaseCount('batches', 1);
    }

    public function test_batch_code_cannot_be_reused(): void
    {
        [$c, $k] = $this->master();
        $op = $this->user('operator');

        $this->actingAs($op)->post(route('batches.store'), $this->payload($c, $k));
        $this->actingAs($op)->post(route('batches.store'), $this->payload($c, $k))->assertSessionHasErrors('code');
    }

    public function test_movement_cannot_exceed_population_and_finish_requires_zero(): void
    {
        [$c, $k] = $this->master();
        $op = $this->user('operator');
        $this->actingAs($op)->post(route('batches.store'), $this->payload($c, $k));
        $b = Batch::firstWhere('code', 'BR-T1');
        $move = fn ($q) => ['type' => 'harvest', 'quantity' => $q, 'movement_date' => now()->toDateString()];

        $this->actingAs($op)->post(route('batches.movement', $b), $move(81))->assertSessionHasErrors('quantity');
        $this->actingAs($op)->post(route('batches.finish', $b))->assertSessionHasErrors('status');

        $this->actingAs($op)->post(route('batches.movement', $b), $move(80))->assertSessionHasNoErrors();
        $this->actingAs($op)->post(route('batches.finish', $b))->assertSessionHasNoErrors();
        $this->assertSame('completed', $b->fresh()->status);
        $this->assertSame('available', $k->fresh()->status);
    }

    public function test_cancel_reverses_doc_cost_and_is_blocked_when_records_exist(): void
    {
        [$c, $k] = $this->master();
        $op = $this->user('operator');
        $this->actingAs($op)->post(route('batches.store'), $this->payload($c, $k));
        $b = Batch::firstWhere('code', 'BR-T1');

        $this->actingAs($op)->post(route('batches.cancel', $b))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $b->fresh()->status);
        $this->assertEquals(0, FinancialTransaction::where('type', 'expense')->sum('amount')); // 560.000 + (-560.000)

        $this->actingAs($op)->post(route('batches.store'), $this->payload($c, $k, ['code' => 'BR-T2']));
        $b2 = Batch::firstWhere('code', 'BR-T2');
        $this->actingAs($op)->post(route('batches.movement', $b2), ['type' => 'death', 'quantity' => 1, 'movement_date' => now()->toDateString()]);
        $this->actingAs($op)->post(route('batches.cancel', $b2))->assertSessionHasErrors('status');
    }

    public function test_cannot_delete_coop_or_commodity_with_batches(): void
    {
        [$c, $k] = $this->master();
        $this->actingAs($this->user('operator'))->post(route('batches.store'), $this->payload($c, $k));
        $admin = $this->user('admin');

        $this->actingAs($admin)->delete(route('coops.destroy', $k))->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('commodities.destroy', $c))->assertSessionHas('error');
        $this->assertNotSoftDeleted($k);
    }
}
