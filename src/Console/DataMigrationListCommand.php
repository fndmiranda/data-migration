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
                            {--path=* : Path to find data migrations}
                            {--tag=* : One or many tags that have data you want to migrate}';

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
        $collection = $this->findMigrations($this->option('path'), $this->option('tag'));

        if ($collection->count()) {
            $rows = $collection->map(function ($reflectionClass) {
                return [
                    'class' => $reflectionClass->getName(),
                    'filename' => $reflectionClass->getFileName(),
                    'tag' => $this->getTag($reflectionClass),
                ];
            });

            $rows->push(new TableSeparator());

            $toolbarMessage = sprintf('<fg=yellow>Total:</> %d data-%s', $collection->count(), Str::plural('migration', $collection->count()));
            $rows->push([new TableCell($toolbarMessage, ['colspan' => 2])]);

            $this->table(['filename', 'class', 'tag'], $rows);
        } else {
            $this->info('No data migration found.');
        }
    }
}
