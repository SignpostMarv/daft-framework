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
		/**
		* @var string
		*/
		$tempnam = tempnam(sys_get_temp_dir(), static::class);

		/**
		* @var string
		*/
		return realpath($tempnam);
	}

	final protected static function tempnamCheck(
		OutputInterface $output
	) : ? string {
		$tempnam = static::tempnam();

		if (
			is_dir($tempnam) ||
			! is_file($tempnam) ||
			! is_writable($tempnam)
		) {
			$output->writeln('could not get temporary filename!');

			return null;
		}

		return $tempnam;
	}
}
