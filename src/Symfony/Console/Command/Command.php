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
    * @var string
    */
    protected static $defaultName = '';

    public static function getDefaultName() : string
    {
        if (method_exists(Base::class, 'getDefaultName')) {
            return (string) parent::getDefaultName();
        }

        return static::$defaultName;
    }

    protected static function tempnam() : string
    {
        return (string) realpath((string) tempnam(sys_get_temp_dir(), static::class));
    }

    /**
    * @return string|null
    */
    final protected static function tempnamCheck(OutputInterface $output)
    {
        $tempnam = static::tempnam();

        if ( ! is_dir($tempnam) && is_file($tempnam) && is_writable($tempnam)) {
            return $tempnam;
        }

        $output->writeln('could not get temporary filename!');
    }
}
