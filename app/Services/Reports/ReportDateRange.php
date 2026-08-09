<?php

namespace App\Services\Reports;

use Carbon\Carbon;

class ReportDateRange
{
    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly string $label,
        public readonly string $rangeKey,
    ) {
    }
}
