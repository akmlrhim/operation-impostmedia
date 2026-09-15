<?php

namespace Tests\Feature\Crm;

use App\Models\Contract;
use App\Support\Documents\DocumentVariables;
use Carbon\Carbon;
use Tests\TestCase;

class DocumentDurationTest extends TestCase
{
    public function test_the_duration_is_spelled_in_days_below_one_month(): void
    {
        $this->assertDuration('2026-09-20', '2026-09-20', '1 (satu) hari');
        $this->assertDuration('2026-09-20', '2026-10-02', '12 (dua belas) hari');
        $this->assertDuration('2026-09-20', '2026-10-06', '16 (enam belas) hari');
        $this->assertDuration('2026-09-20', '2026-10-14', '24 (dua puluh empat) hari');
    }

    public function test_the_duration_uses_days_up_to_twenty_nine_days_before_switching(): void
    {
        $this->assertDuration('2026-09-20', '2026-10-19', '29 (dua puluh sembilan) hari');
    }

    public function test_the_duration_switches_to_months_from_one_month_onwards(): void
    {
        $this->assertDuration('2026-01-01', '2026-01-31', '1 (satu) bulan');
        $this->assertDuration('2026-01-01', '2026-03-31', '3 (tiga) bulan');
        $this->assertDuration('2026-01-01', '2026-12-31', '12 (dua belas) bulan');
    }

    public function test_the_duration_is_a_strip_when_a_date_is_missing(): void
    {
        $this->assertDuration(null, null, '-');
        $this->assertDuration('2026-09-20', null, '-');
        $this->assertDuration(null, '2026-09-20', '-');
    }

    private function assertDuration(?string $start, ?string $end, string $expected): void
    {
        $startDate = $start ? Carbon::parse($start) : null;
        $endDate = $end ? Carbon::parse($end) : null;

        $duration = DocumentVariables::forContract(new Contract([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]))['dokumen.durasi'];

        $this->assertSame($expected, $duration);
    }
}
