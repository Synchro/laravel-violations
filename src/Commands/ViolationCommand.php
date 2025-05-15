<?php

namespace Synchro\Violation\Commands;

use Illuminate\Console\Command;

class ViolationCommand extends Command
{
    public $signature = 'laravel-violations';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
