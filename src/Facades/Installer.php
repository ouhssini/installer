<?php

namespace SoftCortex\Installer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SoftCortex\Installer\Installer
 */
class Installer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SoftCortex\Installer\Installer::class;
    }
}
