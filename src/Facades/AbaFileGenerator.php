<?php

declare(strict_types=1);

namespace EginKaman\AbaFileGenerator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \EginKaman\AbaFileGenerator\Generator\AbaFileGenerator
 */
class AbaFileGenerator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'aba-generator';
    }
}
