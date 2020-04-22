<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Symfony\Console\Command;

use SignpostMarv\DaftRouter\DaftSource;
use SignpostMarv\DaftRouter\Router\Compiler;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FastRouteCacheCommand extends Command
{
	const INT_RETURN_OK = 0;

	const INT_RETURN_FAIL = 1;

	/**
	 * @var string
	 */
	protected static $defaultName = 'daft-framework:router:update-cache';

	public function execute(
		InputInterface $input,
		OutputInterface $output
	) : int {
		$cacheFilename = static::tempnamCheck($output);

		if ( ! is_string($cacheFilename)) {
			return self::INT_RETURN_FAIL;
		}

		unlink($cacheFilename);

		$cacheFilename .= '.cache';

		/**
		 * @var array<int, class-string<DaftSource>>
		 */
		$sources = $input->getArgument('sources');

		$dispatcher_options =
			[
				'cacheFile' => $cacheFilename,
		];

		Compiler::ObtainDispatcher($dispatcher_options, ...$sources);

		$output->write(file_get_contents($cacheFilename) ?: 'false');

		unlink($cacheFilename);

		return self::INT_RETURN_OK;
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
