<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures\Routes;

use SignpostMarv\DaftRouter\DaftRoute;
use SignpostMarv\DaftRouter\DaftRouterAutoMethodCheckingTrait;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
* @template T as array{name:string, value:string, secure:'0'|'1', http:'0':'1', same-site:'lax'|'strict'}
*
* @template-implements DaftRoute<T>
*/
class CookieTest implements DaftRoute
{
    use DaftRouterAutoMethodCheckingTrait;

    public static function DaftRouterHandleRequest(Request $request, array $args) : Response
    {
        $resp = new Response('');

        $cookie = new Cookie(
            (string) $args['name'],
            (string) $args['value'],
            123,
            '',
            null,
            '1' === $args['secure'],
            '1' === $args['http'],
            false,
            (string) $args['same-site']
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

    public static function DaftRouterHttpRoute(array $args, string $method = 'GET') : string
    {
        static::DaftRouterAutoMethodChecking($method);

        return sprintf(
            '/cookie-test/%s/%s/%u/%u/%s',
            $args['name'],
            $args['value'],
            $args['secure'],
            $args['http'],
            $args['same-site']
        );
    }

    public static function DaftRouterHttpRouteArgsTyped(array $args, string $method) : array
    {
        /**
        * @psalm-var T
        */
        $args = $args;

        return $args;
    }
}
