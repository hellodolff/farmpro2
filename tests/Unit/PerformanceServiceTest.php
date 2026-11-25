<?php

namespace Tests\Unit;

use App\Services\PerformanceService;
use PHPUnit\Framework\TestCase;

class PerformanceServiceTest extends TestCase
{
    public function test_formulas(): void
    {
        $p = new PerformanceService;
        $this->assertSame(95.0, $p->livability(1000, 950));
        $this->assertSame(1.5, $p->fcr(3000, 2000));
        $this->assertSame(87.0, $p->hdp(870, 1000));
        $this->assertSame(1.5, $p->fcr(1500, 1000));
        // IP = 95 * 2.0 * 100 / (35 * 1.5) = 361.9 -> 362
        $this->assertSame(362.0, $p->ip(95, 2.0, 1.5, 35));
        $this->assertSame(20.0, $p->roi(2000000, 10000000));
        $this->assertNull($p->fcr(100, 0));
        $this->assertSame(500.0, $p->bepUnits(1000000, 20000, 18000));
    }
}
