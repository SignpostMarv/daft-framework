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

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $cacheFilename = tempnam(sys_get_temp_dir(), static::class);
        unlink($cacheFilename);

        $cacheFilename .= '.cache';

        /**
        * @var string[] $sources
        */
        $sources = $input->getArgument('sources');

        Compiler::ObtainDispatcher(
            [
                'cacheFile' => $cacheFilename,
            ],
            ...$sources
        );

        $output->write(file_get_contents($cacheFilename));

        unlink($cacheFilename);

        return 0;
    }

    protected function configure() : void
    {
        $this->setDescription(
            'Update the cache used by the daft framework router'
        )->addArgument(
            'sources',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'class names for sources'
        );
    }
}
