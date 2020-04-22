<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures\Console\Command;

use SignpostMarv\DaftFramework\Symfony\Console\Command\FastRouteCacheCommand;

class ExecuteCoverageCommand extends FastRouteCacheCommand
{
	/**
	 * @var string
	 */
	protected static $defaultName = 'test:execute-coverage';

	protected static function tempnam() : string
	{
		return (string) false;
	}
}
