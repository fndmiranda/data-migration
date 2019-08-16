<?php

namespace Fndmiranda\DataMigration\Console;

use Fndmiranda\DataMigration\Facades\DataMigration;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Helper\TableCell;

class DataMigrationSyncCommand extends DataMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migration:sync
                            {migration? : The data migration to run}
                            {--path=* : Path to find data migrations}
                            {--tag=* : One or many tags that have data you want to migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize data from a data migration with the database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->argument('migration')) {
            $this->sync($this->argument('migration'));
        } else {
            $collection = $this->findMigrations($this->option('path'), $this->option('tag'));

            if ($collection->count()) {
                foreach ($collection as $class) {
                    $this->sync($class->getName());
                }
            } else {
                $this->info('No data migration found.');
            }
        }
    }

    /**
     * Synchronize a data migration.
     *
     * @param string $migration
     */
    protected function sync(string $migration)
    {
        $this->setMigration($migration);

        if (method_exists($this->getMigration(), 'onStartSync')) {
            $this->getMigration()->onStartSync();
        }

        $this->getOutput()->writeln(sprintf(
            '<comment>Calculating synchronization %s of model %s to table %s:</comment>',
            $migration,
            $this->getMigration()->model(),
            app($this->getMigration()->model())->getTable()
        ));
        $progressBar = $this->output->createProgressBar(count($this->getMigration()->data()));
        $progressBar->start();

        $data = DataMigration::sync($this->getMigration(), $progressBar)->toArray();
        $options = $this->getMigration()->options();
        $this->prepare($data, $options);

        $progressBar->finish();
        $this->getOutput()->newLine();

        $rows = $this->getRows();
        $relationships = $this->getRelationships();

        if (!count($rows) && !count($relationships)) {
            $this->info('Nothing to synchronize.');
        } else {
            if (count($rows)) {
                $this->table($this->getHeaders($options['show']), $rows);
            }

            foreach ($this->getRelationships() as $relationship => $data) {
                if (count($data['rows'])) {
                    $headers = [
                        [new TableCell($relationship, ['colspan' => count($data['headers'])])],
                        $data['headers'],
                    ];

                    $this->table($headers, $data['rows']);
                }
            }
        }

        if (method_exists($this->getMigration(), 'onFinishSync')) {
            $this->getMigration()->onFinishSync();
        }
    }
}
