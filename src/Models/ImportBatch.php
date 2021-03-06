<?php

namespace Fournodes\ImportOperation\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $fillable = [
        'defaults',
        'path',
        'settings',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'defaults' => 'array',
        'path'     => 'string',
        'settings' => 'array',
    ];

    /**
     * Overrides table name as defined in config
     *
     * @var string
     */
    public function getTable()
    {
        return config('fournodes.import-operation.import_batch_table_name');
    }
}
