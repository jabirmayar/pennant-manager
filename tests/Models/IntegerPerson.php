<?php

namespace daacreators\PennantManager\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class IntegerPerson extends Model
{
    protected $table = 'scope_integer_people';

    protected $guarded = [];

    public $timestamps = false;
}
