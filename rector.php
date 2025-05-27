<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src',])
    ->withSets([LevelSetList::UP_TO_PHP_81,])
    ->withPreparedSets(deadCode: true, codeQuality: true, typeDeclarations: true, symfonyCodeQuality: true);