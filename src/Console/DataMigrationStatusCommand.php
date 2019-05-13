<?php

namespace Fndmiranda\DataMigration\Console;

use Illuminate\Console\Command;

class DataMigrationStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migration:status';

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
        dump('DataMigrationStatus');
    }
}
