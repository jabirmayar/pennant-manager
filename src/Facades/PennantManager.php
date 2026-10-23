<?php

namespace daacreators\PennantManager\Facades;

use Illuminate\Support\Facades\Facade;

/** @see \daacreators\PennantManager\PennantManager */
class PennantManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \daacreators\PennantManager\PennantManager::class;
    }
}
