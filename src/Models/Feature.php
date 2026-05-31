<?php

namespace daacreators\PennantManager\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $table = 'features';
    
    protected $fillable = ['name', 'scope', 'value'];
    
    public $timestamps = true;
    
    protected $casts = [
    ];
}