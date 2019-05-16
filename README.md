# Data migrations from Laravel

This package simplifies the migration of application data, allowing you to control for example the settings or a list of permissions for the database.


## Installation

```
composer require fndmiranda/data-migration
```

## Usage

You may generate an data migration of the `data-migration:make` Artisan command:

```terminal
php artisan data-migration:make PermissionDataMigration
```

This command will generate a data migration at `app/DataMigrations/PermissionDataMigration.php`. The data migration will contain the `model`, `data`, and `options` methods.

```php
<?php

namespace App\DataMigrations;

use Fndmiranda\DataMigration\Contracts\DataMigration;

class PermissionDataMigration implements DataMigration
{
    /**
     * Get the model being used by the data migration.
     *
     * @return string
     */
    public function model()
    {
        //
    }

    /**
     * Get the data being used by the data migration.
     *
     * @return mixed
     */
    public function data()
    {
        //
    }

    /**
     * Get the data options being used by the data migration.
     *
     * @return mixed
     */
    public function options()
    {
        //
    }
}
```

### Model example

Example of a permissions model with a relationship for dependencies of type belongsToMany with pivot_example_1 and 
pivot_example_2, and a relationship for brand of type belongsTo to exemplify a data migration.

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'title', 'group', 'brand_id',
    ];

    /**
     * The dependencies that belong to the permission.
     */
    public function dependencies()
    {
        return $this->belongsToMany(Permission::class)->withPivot(['pivot_example_1', 'pivot_example_2']);
    }

    /**
     * Get the brand of the permission.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
```

#### model

The model method specifies the model bound to the data migration class.

```php
/**
 * Get the model being used by the data migration.
 *
 * @return string
 */
public function model()
{
    return \App\Permission::class;
}
```

#### data

The data method specifies the data to be migrated.

```php
/**
 * Get the data being used by the data migration.
 *
 * @return mixed
 */
public function data()
{
    return [
       ['name' => 'product.products.index', 'title' => 'List products', 'group' => 'Product', 'brand' => ['name' => 'Brand test 1']],
       ['name' => 'product.products.show', 'title' => 'Show product', 'group' => 'Product'],
       ['name' => 'product.products.store', 'title' => 'Create product', 'group' => 'Product', 'dependencies' => [
           'product.brands.index', 'product.categories.index',
       ], 'brand' => ['name' => 'Brand test 2']],
       ['name' => 'product.products.update', 'title' => 'Update product', 'group' => 'Product', 'dependencies' => [
           'product.brands.index', 'product.categories.index',
       ]],
       ['name' => 'product.products.destroy', 'title' => 'Delete product', 'group' => 'Product'],

       ['name' => 'product.brands.index', 'title' => 'List brands', 'group' => 'Product', 'brand' => ['name' => 'Brand test 1']],
       ['name' => 'product.brands.show', 'title' => 'Show brand', 'group' => 'Product'],
       ['name' => 'product.brands.store', 'title' => 'Create brand', 'group' => 'Product'],
       ['name' => 'product.brands.update', 'title' => 'Update brand', 'group' => 'Product', 'brand' => ['name' => 'Brand test 2']],
       ['name' => 'product.brands.destroy', 'title' => 'Delete brand', 'group' => 'Product'],
   ];
}
```

#### options

The options method specifies the parameters to be used in the migration.

```php
/**
 * Get the data options being used by the data migration.
 *
 * @return mixed
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
               'show' => ['name', 'title'],
           ],
           [
               'type' => 'belongsTo',
               'relation' => 'brand',
               'identifier' => 'name',
               'show' => ['name', 'title'],
           ],
       ],
   ];
}
```

The following keys are available as parameter:

Key | Description | Type
--- | --- | ---
identifier | Column with unique value to validate status. | string
show | Columns to show in commands output. | array
relations | Relationships, see the keys to relationships. | array


The following keys are available as relationships parameter:

Key | Description | Type
--- | --- | ---
relation | Name of the relationship of the model. | string
type | Model relationship type, `belongsToMany` or `belongsTo`. | string
identifier | Column with unique value to validate status. | string
show | Columns to show in commands output. | array
