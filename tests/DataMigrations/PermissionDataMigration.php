<?php

namespace Fndmiranda\DataMigration\Tests\DataMigrations;

use Fndmiranda\DataMigration\Contracts\DataMigration;
use Fndmiranda\DataMigration\Tests\Entities\Permission;
use Illuminate\Support\Collection;

class PermissionDataMigration implements DataMigration
{
    /**
     * Order to execute this data-migration.
     *
     * @var int
     */
    protected $order = 2;

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
        return Permission::class;
    }

    /**
     * Get the data being used by the data-migrate.
     *
     * @return array|Collection
     */
    public function data()
    {
        return [
            [
                'name' => 'products.index',
                'title' => 'List products',
                'group' => [
                    'name' => 'api',
                ],
            ],
            [
                'name' => 'products.show',
                'title' => 'Show product',
                'group' => [
                    'name' => 'website',
                ],
            ],
            [
                'name' => 'products.store',
                'title' => 'Create product',
                'dependencies' => [
                    [
                        'name' => 'brands.index',
                        'pivot1' => 'Pivot value 1',
                    ],
                    [
                        'name' => 'brands.show',
                    ],
                ],
                'group' => [
                    'name' => 'blog',
                ],
            ],
            [
                'name' => 'products.update',
                'title' => 'Update product',
                'dependencies' => [
                    [
                        'name' => 'brands.index',
                        'pivot2' => 'Pivot value 2',
                    ],
                ],
                'group' => [
                    'name' => 'admin',
                ],
            ],
            [
                'name' => 'products.destroy',
                'title' => 'Delete product',
                'group' => [
                    'name' => 'api',
                ],
            ],
            [
                'name' => 'brands.index',
                'title' => 'List brands',
                'group' => [
                    'name' => 'blog',
                ],
            ],
            [
                'name' => 'brands.show',
                'title' => 'Show brand',
                'group' => [
                    'name' => 'admin',
                ],
            ],
            [
                'name' => 'brands.store',
                'title' => 'Create brand',
                'group' => [
                    'name' => 'website',
                ],
            ],
            [
                'name' => 'brands.update',
                'title' => 'Update brand',
                'group' => [
                    'name' => 'api',
                ],
            ],
            [
                'name' => 'brands.destroy',
                'title' => 'Delete brand',
                'group' => [
                    'name' => 'blog',
                ],
            ],
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
            'show' => ['name', 'title'],
            'relations' => [
                [
                    'type' => 'belongsToMany',
                    'relation' => 'dependencies',
                    'identifier' => 'name',
                    'show' => ['name'],
                ],
                [
                    'type' => 'belongsTo',
                    'relation' => 'group',
                ],
            ],
        ];
    }
}
