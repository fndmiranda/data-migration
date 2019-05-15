<?php

namespace Fndmiranda\DataMigration\Console;

use Fndmiranda\DataMigration\Facades\DataMigration as FacadeDataMigration;

class DataMigrationDiffCommand extends DataMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migration:diff {migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show diff of each data';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setMigration($this->argument('migration'));

        $this->getOutput()->writeln('<comment>Calculating diff...</comment>');
        $data = FacadeDataMigration::diff($this->getMigration())->toArray();
        $options = $this->getMigration()->options();

        $this->table($this->getHeaders(), $this->getRows($data, $options));
    }
}
