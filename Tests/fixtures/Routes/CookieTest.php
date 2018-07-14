<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests\fixtures\Routes;

use InvalidArgumentException;
use SignpostMarv\DaftRouter\DaftRoute;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieTest implements DaftRoute
{
    public static function DaftRouterHandleRequest(Request $request, array $args) : Response
    {
        $resp = new Response('');

        $cookie = new Cookie(
            (string) ($args['name'] ?? null),
            is_string($args['value']) ? $args['value'] : null,
            123,
            '',
            null,
            '1' === $args['secure'],
            '1' === $args['http'],
            false,
            is_string($args['same-site']) ? $args['same-site'] : null
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

        return sprintf(
            '/cookie-test/%s/%s/%u/%u/%s',
            $args['name'],
            $args['value'],
            $args['secure'],
            $args['http'],
            $args['same-site']
        );
    }
}
