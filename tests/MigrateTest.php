<?php

namespace Fndmiranda\DataMigration\Tests;

use Fndmiranda\DataMigration\Console\DataMigrationMigrateCommand;
use Fndmiranda\DataMigration\Tests\DataMigrations\GroupDataMigration;
use Fndmiranda\DataMigration\Tests\DataMigrations\PermissionDataMigration;
use Fndmiranda\DataMigration\Tests\Entities\Group;
use Fndmiranda\DataMigration\Tests\Entities\Permission;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;

class MigrateTest extends TestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test migrate simple data.
     *
     * @return void
     */
    public function test_simple_migrate()
    {
        $migration = GroupDataMigration::class;
        $dataMigration = app($migration);

        Artisan::call('data-migration:migrate', ['migration' => $migration]);

        foreach ($dataMigration->data() as $item) {
            $this->assertDatabaseHas(app($dataMigration->model())->getTable(), $item);
        }
    }

    /**
     * Test migrate with relationship.
     *
     * @return void
     */
    public function test_migrate_with_relationship()
    {
        $migration = PermissionDataMigration::class;
        $dataMigration = app($migration);

        Artisan::call('data-migration:migrate', ['migration' => GroupDataMigration::class]);
        Artisan::call('data-migration:migrate', ['migration' => $migration]);

        $data = collect($dataMigration->data())->map(function ($item) {
            $data = [
                'name' => $item['name'],
                'title' => $item['title'],
                'group_id' => Group::where('name', '=', $item['group']['name'])->first()->id,
            ];

            if (!empty($item['dependencies'])) {
                $data['dependencies'] = $item['dependencies'];
            }

            return $data;
        })->toArray();

        foreach ($data as $item) {
            $this->assertDatabaseHas(app($dataMigration->model())->getTable(), Arr::except($item, ['dependencies']));

            if (!empty($item['dependencies'])) {
                $permission = Permission::where('name', '=', $item['name'])->first();

                foreach ($item['dependencies'] as $dependency) {
                    $hasDependency = (bool) $permission->dependencies()->where(function ($query) use ($dependency) {
                        foreach ($dependency as $key => $item) {
                            $query->where($key, '=', $item);
                        }
                    })->count();

                    $this->assertTrue($hasDependency);
                }
            }
        }
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        Artisan::call('migrate:reset');
        parent::tearDown();
    }
}
