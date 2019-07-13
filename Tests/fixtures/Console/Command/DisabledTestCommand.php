<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures\Console\Command;

use SignpostMarv\DaftFramework\Symfony\Console\Command\Command;

class DisabledTestCommand extends Command
{
	/**
	* @var string
	*/
	protected static $defaultName = 'test:disabled';

	public function isEnabled() : bool
	{
		return false;
	}
}
