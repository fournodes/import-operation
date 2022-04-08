<?php

namespace Fournodes\ImportOperation\Models;

use Illuminate\Database\Eloquent\Model;

class ImportMapping extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'model_type',
        'mapping',
    ];

    /**
    * The attributes that should be cast to native types.
    *
    * @var array
    */
    protected $casts = [
        'mapping' => 'array',
    ];

    /**
     * Overrides table name as defined in config
     *
     * @var string
     */
    public function getTable()
    {
        return config('fournodes.import-operation.import_mapping_table_name');
    }
}
