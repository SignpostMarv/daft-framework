<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures\Console\Command;

use SignpostMarv\DaftFramework\Symfony\Console\Command\Command;

class TestCommand extends Command
{
    protected static $defaultName = 'test';
}
