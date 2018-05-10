<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures;

use SignpostMarv\DaftFramework\Symfony\Console\DaftConsoleSource;
use SignpostMarv\DaftFramework\Tests\fixtures\Console\Command;

return [
    DaftConsoleSource::class => [
        Command\TestCommand::class,
        Command\DisabledTestCommand::class,
    ],
];
