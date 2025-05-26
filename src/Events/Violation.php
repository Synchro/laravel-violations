<?php

declare(strict_types=1);

namespace Synchro\Violation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Synchro\Violation\Models\Violation as ViolationModel;

class Violation
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public ViolationModel $violation,
    ) {}
}
