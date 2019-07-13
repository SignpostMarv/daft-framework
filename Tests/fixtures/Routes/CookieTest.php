<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures\Routes;

use SignpostMarv\DaftRouter\DaftRouteAcceptsOnlyTypedArgs;
use SignpostMarv\DaftRouter\DaftRouterHttpRouteDefaultMethodGet;
use SignpostMarv\DaftRouter\TypedArgs;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
* @psalm-type T1 = array{name:string, value:string, secure:bool, http:bool, same-site:'lax'|'strict'}
* @psalm-type T2 = CookieTestArgs
* @psalm-type T3 = array{name:string, value:string, secure:'0'|'1', http:'0'|'1', same-site:'lax'|'strict'}
*
* @template-extends DaftRouteAcceptsOnlyTypedArgs<T1, T3, T2, Response, 'GET', 'GET'>
*/
class CookieTest extends DaftRouteAcceptsOnlyTypedArgs
{
	use DaftRouterHttpRouteDefaultMethodGet;

	/**
	* @param T2 $args
	*/
	public static function DaftRouterHandleRequestWithTypedArgs(Request $request, TypedArgs $args) : Response
	{
		static::DaftRouterAutoMethodChecking($request->getMethod());

		$resp = new Response('');

		$cookie = new Cookie(
			$args->name,
			$args->value,
			123,
			'',
			null,
			$args->secure,
			$args->http,
			false,
			$args->SameSite()
		);

		$resp->headers->setCookie($cookie);

		return $resp;
	}

	public static function DaftRouterRoutes() : array
	{
		return [
			'/cookie-test/{name:[^\/]+}/{value:[^\/]+}/{secure:[0-1]}/{http:[0-1]}/{same-site:(?:lax|strict)}' => ['GET'],
		];
	}

	/**
	* @param T2 $args
	* @param 'GET'|null $method
	*/
	public static function DaftRouterHttpRouteWithTypedArgs(
		TypedArgs $args,
		string $method = null
	) : string {
		return sprintf(
			'/cookie-test/%s/%s/%u/%u/%s',
			$args->name,
			$args->value,
			$args->secure,
			$args->http,
			$args->SameSite()
		);
	}

	/**
	* @param T3 $args
	* @param 'GET'|null $method
	*
	* @return T2
	*/
	public static function DaftRouterHttpRouteArgsTyped(array $args, string $method = null)
	{
		return new CookieTestArgs($args);
	}
}
