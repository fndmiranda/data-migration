<?php

namespace Fndmiranda\DataMigration\Tests;

use Fndmiranda\DataMigration\Console\DataMigrationMigrateCommand;
use Fndmiranda\DataMigration\Tests\DataMigrations\GroupDataMigration;
use Fndmiranda\DataMigration\Tests\DataMigrations\PermissionDataMigration;
use Fndmiranda\DataMigration\Tests\Entities\Group;
use Fndmiranda\DataMigration\Tests\Entities\Permission;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;

class SyncTest extends TestCase
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
    public function test_simple_sync()
    {
        $migration = GroupDataMigration::class;
        $dataMigration = app($migration);

        Group::insert([
            ['title' => 'ApiChange', 'name' => 'api-change', 'is_active' => false],
            ['title' => 'Admin', 'name' => 'admin', 'is_active' => true],
            ['title' => 'Website', 'name' => 'website', 'is_active' => true],
            ['title' => 'BlogChange', 'name' => 'blog-change', 'is_active' => false],
            ['title' => 'Warehouse', 'name' => 'warehouse', 'is_active' => true],
            ['title' => 'Payment', 'name' => 'payment', 'is_active' => true],
        ]);

        Artisan::call('data-migration:sync', ['migration' => $migration]);

        $this->assertEquals(Group::count(), count($dataMigration->data()));

        foreach ($dataMigration->data() as $item) {
            $this->assertDatabaseHas(app($dataMigration->model())->getTable(), $item);
        }
    }

    /**
     * Test sync with relationship.
     *
     * @return void
     */
    public function test_sync_with_relationship()
    {
        $migration = PermissionDataMigration::class;
        $dataMigration = app($migration);

        Artisan::call('data-migration:migrate', ['migration' => GroupDataMigration::class]);

        $groupApi = Group::where('name', '=', 'api')->first()->id;
        $groupWebsite = Group::where('name', '=', 'website')->first()->id;

        $permission1 = Permission::create([
            'name' => 'products.index',
            'title' => 'List products',
            'group_id' => $groupWebsite,
        ]);

        Permission::create([
            'name' => 'products.store',
            'title' => 'Create product change',
            'group_id' => $groupApi,
        ])->dependencies()->attach($permission1->id, ['pivot1' => 'Example test pivot 1']);

        Permission::create([
            'name' => 'products.show',
            'title' => 'Show product',
            'group_id' => $groupApi,
        ])->dependencies()->attach($permission1->id, ['pivot1' => 'Example test pivot 2']);

        Artisan::call('data-migration:sync', ['migration' => $migration]);

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
