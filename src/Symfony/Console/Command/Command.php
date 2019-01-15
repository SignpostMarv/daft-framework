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

    protected static function tempnam() : string
    {
        return strval(tempnam(sys_get_temp_dir(), static::class));
    }

    final protected static function tempnamCheck(OutputInterface $output) : ? string
    {
        $tempnam = realpath(static::tempnam());

        if ( ! is_string($tempnam) || ! is_writeable($tempnam) || is_dir($tempnam)) {
            $output->writeln('could not get temporary filename!');

            return null;
        }

        return $tempnam;
    }
}
