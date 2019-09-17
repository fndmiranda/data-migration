<?php

namespace Fndmiranda\DataMigration\Console;

use Fndmiranda\DataMigration\Facades\DataMigration;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Helper\TableCell;

class DataMigrationMigrateCommand extends DataMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migration:migrate
                            {migration? : The data migration to run}
                            {--path=* : Path to find data migrations}
                            {--tag=* : One or many tags that have data you want to migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from a data migration to the database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->argument('migration')) {
            $this->migrate($this->argument('migration'));
        } else {
            $collection = $this->findMigrations($this->option('path'), $this->option('tag'));

            if ($collection->count()) {
                foreach ($collection as $class) {
                    $this->migrate($class->getName());
                }
            } else {
                $this->info('No data migration found.');
            }
        }
    }

    /**
     * Migrate a data migration.
     *
     * @param string $migration
     */
    protected function migrate(string $migration)
    {
        $this->setMigration($migration);

        if (method_exists($this->getMigration(), 'onStartMigrate')) {
            $this->getMigration()->onStartMigrate();
        }

        $this->getOutput()->writeln(sprintf(
            '<comment>Calculating migrate %s of model %s to table %s:</comment>',
            $migration,
            $this->getMigration()->model(),
            app($this->getMigration()->model())->getTable()
        ));
        $progressBar = $this->output->createProgressBar(count($this->getMigration()->data()));
        $progressBar->start();

        $data = DataMigration::migrate($this->getMigration(), $progressBar)->toArray();
        $options = $this->getMigration()->options();
        $this->prepare($data, $options);

        $progressBar->finish();
        $this->getOutput()->newLine();

        $rows = Arr::where($this->getRows(), function ($value) {
            return $value['status'] == '<fg=green>Create</fg=green>';
        });

        $relationships = $this->getRelationships();

        if (!count($rows) && !count($relationships)) {
            $this->info('Nothing to migrate.');
        } else {
            if (count($rows)) {
                $this->table($this->getHeaders($options['show']), $rows);
            }

            foreach ($this->getRelationships() as $relationship => $data) {
                $relation_rows = Arr::where($data['rows'], function ($value) {
                    return $value['status'] == '<fg=green>Create</fg=green>';
                });

                if (count($relation_rows)) {
                    $headers = [
                        [new TableCell($relationship, ['colspan' => count($data['headers'])])],
                        $data['headers'],
                    ];

                    $this->table($headers, $relation_rows);
                }
            }
        }

        if (method_exists($this->getMigration(), 'onFinishMigrate')) {
            $this->getMigration()->onFinishMigrate();
        }
    }
}
