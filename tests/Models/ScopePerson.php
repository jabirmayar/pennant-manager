<?php

namespace daacreators\PennantManager\Tests\Models;

use Illuminate\Foundation\Auth\User;

class ScopePerson extends User
{
    protected $table = 'scope_people';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    protected $guarded = [];

    public $incrementing = false;

    public $timestamps = false;
}
