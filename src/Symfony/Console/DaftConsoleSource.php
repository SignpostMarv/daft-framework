<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Symfony\Console;

interface DaftConsoleSource
{
	/**
	 * Provides an array of Command\Command implmentations, or DaftConsoleSource implementations.
	 *
	 * @return array<int, string>
	 */
	public static function DaftFrameworkConsoleSources() : array;
}
