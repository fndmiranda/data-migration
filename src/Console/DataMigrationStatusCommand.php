<?php

namespace Fndmiranda\DataMigration\Console;

use Fndmiranda\DataMigration\Facades\DataMigration;

class DataMigrationStatusCommand extends DataMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migration:status {migration}';

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
        $this->setMigration($this->argument('migration'));

        $this->getOutput()->writeln('<comment>Calculating status...</comment>');
        $data = DataMigration::status($this->getMigration())->toArray();
        $options = $this->getMigration()->options();
        $rows = $this->getRows($data, $options);

        if (count($rows)) {
            $this->table($this->getHeaders(), $rows);
        } else {
            $this->info('Nothing to do.');
        }
    }
}
