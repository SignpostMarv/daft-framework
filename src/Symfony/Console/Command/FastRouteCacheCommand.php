<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Symfony\Console\Command;

use SignpostMarv\DaftRouter\Router\Compiler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FastRouteCacheCommand extends Command
{
    /**
    * @var string
    */
    protected static $defaultName = 'daft-framework:router:update-cache';

    public function configure() : void
    {
        $this->setDescription(
            'Update the cache used by the daft framework router'
        )->addArgument(
            'sources',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'class names for sources'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $cacheFilename = tempnam(sys_get_temp_dir(), static::class);
        unlink($cacheFilename);

        $cacheFilename .= '.cache';

        $compiler = Compiler::ObtainDispatcher(
            [
                'cacheFile' => $cacheFilename,
            ],
            ...$input->getArgument('sources')
        );

        $output->write(file_get_contents($cacheFilename));

        unlink($cacheFilename);

        return 0;
    }
}
