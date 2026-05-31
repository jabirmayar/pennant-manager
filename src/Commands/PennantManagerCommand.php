<?php

namespace daacreators\PennantManager\Commands;

use Illuminate\Console\Command;

class PennantManagerCommand extends Command
{
    public $signature = 'pennant-manager';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
