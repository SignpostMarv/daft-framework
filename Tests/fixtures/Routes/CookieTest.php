<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures\Routes;

use SignpostMarv\DaftRouter\DaftRoute;
use SignpostMarv\DaftRouter\DaftRouterAutoMethodCheckingTrait;
use SignpostMarv\DaftRouter\TypedArgs;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
* @template T1 as array{name:string, value:string, secure:bool, http:bool, same-site:'lax'|'strict'}
* @template T2 as CookieTestArgs
* @template T3 as array{name:string, value:string, secure:'0'|'1', http:'0'|'1', same-site:'lax'|'strict'}
*
* @template-implements DaftRoute<T1, T2>
*/
class CookieTest implements DaftRoute
{
    use DaftRouterAutoMethodCheckingTrait;

    /**
    * @param T2 $args
    */
    public static function DaftRouterHandleRequest(Request $request, TypedArgs $args) : Response
    {
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
    */
    public static function DaftRouterHttpRoute(TypedArgs $args, string $method = 'GET') : string
    {
        static::DaftRouterAutoMethodChecking($method);

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
    */
    public static function DaftRouterHttpRouteArgsTyped(array $args, string $method) : TypedArgs
    {
        static::DaftRouterAutoMethodChecking($method);

        return new CookieTestArgs($args);
    }
}
