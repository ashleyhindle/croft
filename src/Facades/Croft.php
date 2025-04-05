<?php

declare(strict_types=1);

namespace Croft\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Croft\Croft
 */
class Croft extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Croft\Croft::class;
    }
}
