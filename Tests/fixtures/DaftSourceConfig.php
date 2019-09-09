<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures;

use SignpostMarv\DaftFramework\Http\CookieMiddleware;
use SignpostMarv\DaftRouter;

class DaftSourceConfig implements DaftRouter\DaftSource
{
	public static function DaftRouterRouteAndMiddlewareSources() : array
	{
		return [
			CookieMiddleware::class,
			DaftRouter\Tests\Fixtures\Home::class,
			DaftRouter\Tests\Fixtures\Login::class,
			DaftRouter\Tests\Fixtures\NotLoggedIn::class,
			DaftRouter\Tests\Fixtures\AppendHeader::class,
			DaftRouter\Tests\Fixtures\DoesNothing::class,
		];
	}
}
