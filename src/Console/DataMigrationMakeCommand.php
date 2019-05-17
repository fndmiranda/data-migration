<?php

namespace Fndmiranda\DataMigration\Console;

use Illuminate\Console\GeneratorCommand;

class DataMigrationMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'data-migration:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new data migration file';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'DataMigration';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/data-migration.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\DataMigrations';
    }
}
