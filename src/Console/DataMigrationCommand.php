<?php

namespace Fndmiranda\DataMigration\Console;

use Fndmiranda\DataMigration\Contracts\DataMigration as DataMigrationContract;
use Fndmiranda\DataMigration\DataMigration;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

abstract class DataMigrationCommand extends Command
{
    /**
     * The status column mapping formatted.
     *
     * @var array
     */
    protected $status = [
        DataMigration::OK => '<fg=white>OK</fg=white>',
        DataMigration::CREATE => '<fg=green>Create</fg=green>',
        DataMigration::UPDATE => '<fg=yellow>Update</fg=yellow>',
        DataMigration::DELETE => '<fg=red>Delete</fg=red>',
        DataMigration::NOT_FOUND => '<fg=blue>Not found</fg=blue>',
    ];

    /**
     * The migration instance.
     *
     * @var DataMigrationContract
     */
    protected $migration;

    /**
     * The data migration rows.
     *
     * @var array
     */
    protected $rows = [];

    /**
     * Relationship tables.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Constant representing default tag.
     *
     * @var string
     */
    const TAG = 'production';

    /**
     * Get a data migration instance.
     *
     * @return DataMigrationContract
     */
    protected function getMigration()
    {
        return $this->migration;
    }

    /**
     * Set a data migration instance.
     *
     * @param string $migration
     * @return $this
     */
    protected function setMigration($migration)
    {
        $this->migration = $this->laravel->make($migration);

        return $this;
    }

    protected function prepare($data, $options)
    {
        $relations = Arr::get($options, 'relations', []);
        $this->rows = [];

        foreach ($data as $key => $item) {
            $this->rows[] = $this->getRow($item, $options['show']);

            foreach ($relations as $relation) {
                if (Arr::has($item['data'], $relation['relation'])) {
                    switch ($relation['type']) {
                        case DataMigration::BELONGS_TO_MANY:
                            if (!Arr::has($this->relationships, $relation['relation'])) {
                                Arr::set($this->relationships, $relation['relation'], ['rows' => []]);
                            }

                            $this->relationships[$relation['relation']]['headers'] = $this->getHeaders($relation['show']);

                            foreach ($item['data'][$relation['relation']] as $element) {
                                $this->relationships[$relation['relation']]['rows'][] = $this->getRow($element, $relation['show']);
                            }
                            break;
                        case DataMigration::BELONGS_TO:
                            if (!Arr::has($this->relationships, $relation['relation'])) {
                                Arr::set($this->relationships, $relation['relation'], ['rows' => []]);
                            }

                            $show = array_keys($item['data'][$relation['relation']]['data']);

                            $this->relationships[$relation['relation']]['headers'] = $this->getHeaders($show);
                            $this->relationships[$relation['relation']]['rows'][] = $this->getRow($item['data'][$relation['relation']], $show);
                            break;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Get the relationships.
     *
     * @return array
     */
    protected function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * Get the rows.
     *
     * @return array
     */
    protected function getRows()
    {
        return $this->rows;
    }

    /**
     * Get the row.
     *
     * @param array $value
     * @param array $headers
     * @return array
     */
    protected function getRow($value, $headers)
    {
        $columns = [];
        foreach ($headers as $column) {
            $columns[$column] = Arr::get($value['data'], $column);
        }
        $columns['status'] = $this->status[$value['status']];

        return $columns;
    }

    /**
     * Get the headers.
     *
     * @param array $values
     * @return mixed
     */
    protected function getHeaders($values)
    {
        $headers = $values;
        $headers[] = 'status';

        return $headers;
    }

    /**
     * Get tag of the data-migration in reflection class.
     *
     * @param ReflectionClass $reflectionClass
     * @return mixed|string
     * @throws \ReflectionException
     */
    protected function getTag(ReflectionClass $reflectionClass)
    {
        if ($reflectionClass->hasProperty('tag')) {
            $reflectionProperty = $reflectionClass->getProperty('tag');
            $reflectionProperty->setAccessible(true);

            $propertyValue = $reflectionProperty->getValue(app($reflectionClass->getName()));
            if (!is_null($propertyValue)) {
                return $propertyValue;
            }
        }

        return self::TAG;
    }

    /**
     * Find data migrations.
     *
     * @param array $path Path to find data migrations
     * @param array $tag One or many tags that have data you want to migrate
     * @return mixed|Collection
     */
    protected function findMigrations(array $path = [], array $tag = [])
    {
        $path = empty($path) ? app_path() : $path;
        $tags = empty($tag) ? [self::TAG] : $tag;
        $finder = new Finder();

        $finder->name('*.php')->notName('*.blade.php')->files()->in($path);

        foreach ($finder as $file) {
            require_once $file->getPathname();
        }

        return collect(get_declared_classes())
            ->map(function ($reflectionClass) {
                return new ReflectionClass($reflectionClass);
            })
            ->filter(function ($reflectionClass) use ($path) {
                return $reflectionClass->isSubclassOf(DataMigrationContract::class);
            })
            ->filter(function ($reflectionClass) use ($tags) {
                return in_array($this->getTag($reflectionClass), $tags);
            })
            ->sortBy(function ($reflectionClass) {
                if ($reflectionClass->hasProperty('order')) {
                    $reflectionProperty = $reflectionClass->getProperty('order');
                    $reflectionProperty->setAccessible(true);

                    return (int) $reflectionProperty->getValue(app($reflectionClass->getName()));
                }

                return 0;
            })
            ->values();
    }
}
