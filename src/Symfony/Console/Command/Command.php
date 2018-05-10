<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Symfony\Console\Command;

use BadMethodCallException;
use SignpostMarv\DaftFramework\AttachDaftFramework;
use Symfony\Component\Console\Command\Command as Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends Base
{
    use AttachDaftFramework;

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        throw new BadMethodCallException('command body not implemented!');
    }
}
