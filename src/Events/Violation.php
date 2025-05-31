<?php

declare(strict_types=1);

namespace Synchro\Violation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\LaravelData\Data;
use Synchro\Violation\Enums\ReportSource;

class Violation
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Data $report,
        public ReportSource $reportSource,
        public ?string $userAgent = null,
        public ?string $ip = null,
    ) {}
}
