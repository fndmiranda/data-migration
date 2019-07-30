<?php

namespace Fndmiranda\DataMigration\Tests\DataMigrations;

use Fndmiranda\DataMigration\Contracts\DataMigration;
use Fndmiranda\DataMigration\Tests\Entities\Group;
use Illuminate\Support\Collection;

class GroupDataMigration implements DataMigration
{
    /**
     * Order to execute this data-migration.
     *
     * @var int
     */
    protected $order = 1;

    /**
     * Tag to filter on data-migrations search.
     *
     * @var string
     */
    protected $tag = 'production';

    /**
     * Get the model being used by the data-migrate.
     *
     * @return string
     */
    public function model()
    {
        return Group::class;
    }

    /**
     * Get the data being used by the data-migrate.
     *
     * @return array|Collection
     */
    public function data()
    {
        return [
            ['title' => 'Api', 'name' => 'api', 'is_active' => true],
            ['title' => 'Admin', 'name' => 'admin', 'is_active' => true],
            ['title' => 'Website', 'name' => 'website', 'is_active' => true],
            ['title' => 'Blog', 'name' => 'blog', 'is_active' => true],
        ];
    }

    /**
     * Get the data options being used by the data-migrate.
     *
     * @return array|Collection
     */
    public function options()
    {
        return [
            'identifier' => 'name',
            'show' => ['name', 'title', 'is_active'],
        ];
    }
}
