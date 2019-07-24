<?php

namespace Fndmiranda\DataMigration\Console;

use Fndmiranda\DataMigration\Facades\DataMigration;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

class DataMigrationListCommand extends DataMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migration:list
                            {--path=* : Path to find data migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists data-migrations';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $collection = $this->findMigrations($this->option('path'));

        if ($collection->count()) {
            $rows = $collection->map(function ($migration) {
                return [
                    'class' => $migration->getName(),
                    'filename' => $migration->getFileName(),
                ];
            });

            $rows->push(new TableSeparator());

            $toolbarMessage = sprintf('<fg=yellow>Total:</> %d data-%s', $collection->count(), Str::plural('migration', $collection->count()));
            $rows->push([new TableCell($toolbarMessage, ['colspan' => 2])]);

            $this->table(['class', 'filename'], $rows);
        } else {
            $this->info('No data migration found.');
        }
    }
}
