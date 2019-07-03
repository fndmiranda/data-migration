<?php

namespace Fndmiranda\DataMigration\Console;

use Fndmiranda\DataMigration\Facades\DataMigration;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Helper\TableCell;

class DataMigrationDiffCommand extends DataMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migration:diff
                            {migration? : The data migration to run}
                            {--path=* : Path to find data migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show changes between data migration and database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->argument('migration')) {
            $this->diff($this->argument('migration'));
        } else {
            $collection = $this->findMigrations($this->option('path'));

            if ($collection->count()) {
                foreach ($collection as $class) {
                    $this->diff($class->getName());
                }
            } else {
                $this->info('No data migration found.');
            }
        }
    }

    /**
     * Diff of a data migration.
     *
     * @param string $migration
     */
    protected function diff(string $migration)
    {
        $this->setMigration($migration);

        $this->getOutput()->writeln(sprintf('<comment>Calculating diff to %s:</comment>', $this->getMigration()->model()));
        $progressBar = $this->output->createProgressBar(count($this->getMigration()->data()));
        $progressBar->start();

        $data = DataMigration::diff($this->getMigration(), $progressBar)->toArray();
        $options = $this->getMigration()->options();
        $this->prepare($data, $options);

        $progressBar->finish();
        $this->getOutput()->newLine();

        $rows = Arr::where($this->getRows(), function ($value) {
            return $value['status'] != '<fg=white>OK</fg=white>';
        });

        $relationships = $this->getRelationships();

        if (!count($rows) && !count($relationships)) {
            $this->info('Nothing to diff.');
        } else {
            if (count($rows)) {
                $this->table($this->getHeaders($options['show']), $rows);
            }

            foreach ($this->getRelationships() as $relationship => $data) {
                $headers = [
                    [new TableCell($relationship, ['colspan' => count($data['headers'])])],
                    $data['headers'],
                ];

                $this->table($headers, $data['rows']);
            }
        }
    }
}
