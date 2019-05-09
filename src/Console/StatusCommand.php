<?php

namespace Fndmiranda\DataMigrate\Console;

use Illuminate\Console\Command;

class StatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migrate:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each data';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        dump('StatusCommand');
    }
}
