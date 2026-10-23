<?php

namespace daacreators\PennantManager\Models;

use daacreators\PennantManager\Support\FeatureStorage;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    public function getTable()
    {
        return FeatureStorage::table();
    }

    public function getConnectionName()
    {
        return FeatureStorage::connectionName() ?? parent::getConnectionName();
    }

    protected $table = 'features';

    protected $fillable = ['name', 'scope', 'value'];

    public $timestamps = true;

    protected $casts = [
    ];
}
