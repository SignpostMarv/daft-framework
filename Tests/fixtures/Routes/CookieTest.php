<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures\Routes;

use InvalidArgumentException;
use SignpostMarv\DaftRouter\DaftRoute;
use SignpostMarv\DaftRouter\DaftRouterAutoMethodCheckingTrait;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieTest implements DaftRoute
{
    use DaftRouterAutoMethodCheckingTrait;

    /**
    * @psalm-suppress TooManyArguments
    */
    public static function DaftRouterHandleRequest(Request $request, array $args) : Response
    {
        /**
        * @var array<string, string>
        */
        $args = $args;

        $args = static::DaftRouterHttpRouteArgsTyped($args, $request->getMethod());

        $resp = new Response('');

        $objArgs = [];

        if (method_exists(Cookie::class, 'getSameSite')) {
            $objArgs[] = false;
            $objArgs[] = is_string($args['same-site']) ? $args['same-site'] : null;
        }

        $cookie = new Cookie(
            (string) ($args['name'] ?? null),
            is_string($args['value']) ? $args['value'] : null,
            123,
            '',
            null,
            '1' === $args['secure'],
            '1' === $args['http'],
            ...$objArgs
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
    * @param array<string, string> $args
    */
    public static function DaftRouterHttpRoute(array $args, string $method = 'GET') : string
    {
        $args = static::DaftRouterHttpRouteArgs($args, $method);

        return sprintf(
            '/cookie-test/%s/%s/%u/%u/%s',
            $args['name'],
            $args['value'],
            $args['secure'],
            $args['http'],
            $args['same-site']
        );
    }

    /**
    * @param array<string, string> $args
    *
    * @return array<string, string>
    *
    * @psalm-return array{name:string, value:string, secure:string, http:string, same-site:string}
    */
    public static function DaftRouterHttpRouteArgs(array $args, string $method) : array
    {
        static::DaftRouterAutoMethodChecking($method);

        if (
            ! isset(
                $args['name'],
                $args['value'],
                $args['secure'],
                $args['http'],
                $args['same-site']
            )
        ) {
            throw new InvalidArgumentException('cookie args not specified!');
        }

        return [
            'name' => $args['name'],
            'value' => $args['value'],
            'secure' => $args['secure'],
            'http' => $args['http'],
            'same-site' => $args['same-site'],
        ];
    }

    /**
    * @param array<string, string> $args
    *
    * @return array<string, string>
    *
    * @psalm-return array{name:scalar, value:scalar, secure:scalar, http:scalar, same-site:scalar}
    */
    public static function DaftRouterHttpRouteArgsTyped(array $args, string $method) : array
    {
        return static::DaftRouterHttpRouteArgs($args, $method);
    }
}
