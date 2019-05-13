# Data migrations from Laravel

This package simplifies the migration of application data, allowing you to control for example the settings or a list of permissions for the database.


## Installation

```
composer require fndmiranda/data-migration
```

## Generate data migrate class

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
       ['name' => 'product.products.index', 'title' => 'List products'],
       ['name' => 'product.products.show', 'title' => 'Show product'],
       ['name' => 'product.products.store', 'title' => 'Create product', 'dependencies' => [
           'product.brands.index', 'product.categories.index',
       ]],
       ['name' => 'product.products.update', 'title' => 'Update product', 'dependencies' => [
           'product.brands.index', 'product.categories.index',
       ]],
       ['name' => 'product.products.destroy', 'title' => 'Delete product'],

       ['name' => 'product.brands.index', 'title' => 'List brands'],
       ['name' => 'product.brands.show', 'title' => 'Show brand'],
       ['name' => 'product.brands.store', 'title' => 'Create brand'],
       ['name' => 'product.brands.update', 'title' => 'Update brand'],
       ['name' => 'product.brands.destroy', 'title' => 'Delete brand'],
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
