<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Symfony\Console\Command;

use SignpostMarv\DaftFramework\AttachDaftFramework;
use Symfony\Component\Console\Command\Command as Base;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends Base
{
    use AttachDaftFramework;

    /**
    * @return string|false
    */
    protected static function tempnam()
    {
        return tempnam(sys_get_temp_dir(), static::class);
    }

    /**
    * @return string|false
    */
    final protected static function tempnamCheck(OutputInterface $output)
    {
        $tempnam = static::tempnam();

        if ( ! is_string($tempnam)) {
            $output->writeln('could not get temporary filename!');
        }

        return $tempnam;
    }
}
