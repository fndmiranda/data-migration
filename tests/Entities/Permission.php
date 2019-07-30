<?php

namespace Fndmiranda\DataMigration\Tests\Entities;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'title', 'group_id',
    ];

    /**
     * Get the group of the permission.
     */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }

    /**
     * The dependencies that belong to the permission.
     */
    public function dependencies()
    {
        return $this->belongsToMany(
            Permission::class,
            'dependency_permission',
            'dependent_id',
            'dependency_id'
        )->withPivot(['pivot1', 'pivot2']);
    }
}
