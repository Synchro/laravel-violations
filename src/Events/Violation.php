<?php

namespace Synchro\Violation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Synchro\Violation\Models\Violation as ViolationModel;

class Violation
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ViolationModel $violation,
    ) {}
}
