<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests;

use Generator;
use PHPUnit\Framework\TestCase as Base;
use SignpostMarv\DaftFramework\Http\CookieMiddleware;
use SignpostMarv\DaftFramework\HttpHandler;
use SignpostMarv\DaftRouter\DaftSource;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class CookieMiddlewareTest extends Base
{
    /**
    * @dataProvider DataProvderCookeMiddlewareTest
    */
    public function testCookieMiddleware(
        string $implementation,
        array $postConstructionCalls,
        string $baseUrl,
        string $basePath,
        array $config,
        string $cookieName,
        string $cookieValue,
        ? string $secure,
        ? string $http,
        ? string $sameSite
    ) : void {
        $url = sprintf(
            'cookie-test/%s/%s/%s/%s/%s',
            rawurlencode($cookieName),
            rawurlencode($cookieValue),
            rawurlencode($secure ?? '1'),
            rawurlencode($http ?? '1'),
            rawurlencode($sameSite ?? 'lax')
        );

        if (is_string($secure) && is_string($http) && is_string($sameSite)) {
        $config[CookieMiddleware::class] = [
            'secure' => '1' !== $secure,
            'httpOnly' => '1' !== $http,
            'sameSite' => (
                ('lax' === $sameSite)
                    ? 'strict'
                    : 'lax'
            ),
        ];
        }

        $config[DaftSource::class]['sources'] = [
            fixtures\Routes\CookieTest::class,
        ];
        $config[DaftSource::class]['cacheFile'] = (__DIR__ . '/fixtures/cookie-test.fast-route.cache');

        $instance = Utilities::ObtainHttpHandlerInstance(
            $this,
            $implementation,
            $baseUrl,
            $basePath,
            $config
        );

        $request = Request::create($baseUrl . $url);

        $response = $instance->handle($request);

        $cookie = current(array_filter(
            $response->headers->getCookies(),
            function (Cookie $cookie) use ($cookieName) : bool {
                return $cookieName === $cookie->getName();
            }
        ));

        $this->assertInstanceOf(Cookie::class, $cookie);

        if (is_string($secure) && is_string($http) && is_string($sameSite)) {
        $this->assertSame('1' === $secure, $cookie->isSecure(), 'Secure must match without middleware');
        $this->assertSame('1' === $http, $cookie->isHttpOnly(), 'HttpOnly must match without middleware');
        $this->assertSame($sameSite, $cookie->getSameSite(), 'SameSite must match without middleware');
        }

        $config[DaftSource::class]['sources'] = [
            fixtures\Routes\CookieTest::class,
            CookieMiddleware::class,
        ];
        $config[DaftSource::class]['cacheFile'] = (__DIR__ . '/fixtures/cookie-middleware.fast-route.cache');

        $instance = Utilities::ObtainHttpHandlerInstance(
            $this,
            $implementation,
            $baseUrl,
            $basePath,
            $config
        );

        $request = Request::create($baseUrl . $url);

        $response = $instance->handle($request);

        $cookie = current(array_filter(
            $response->headers->getCookies(),
            function (Cookie $cookie) use ($cookieName) : bool {
                return $cookieName === $cookie->getName();
            }
        ));

        $this->assertInstanceOf(Cookie::class, $cookie);

        if (is_string($secure) && is_string($http) && is_string($sameSite)) {
        $this->assertSame($config[CookieMiddleware::class]['secure'], $cookie->isSecure(), 'Secure must match flipped value with middleware');
        $this->assertSame($config[CookieMiddleware::class]['httpOnly'], $cookie->isHttpOnly(), 'HttpOnly must match flipped value with middleware');
        $this->assertSame($config[CookieMiddleware::class]['sameSite'], $cookie->getSameSite(), 'SameSite must match flipped value with middleware');
        }
    }

    public function DataProvderCookeMiddlewareTest() : Generator
    {
        foreach ($this->DataProviderCookieNameValue() as $cookie) {
                        foreach ($this->DataProviderHttpHandlerInstances() as $handlerArgs) {
                            yield array_merge($handlerArgs, $cookie, [null, null, null]);
            foreach ($this->DataProviderCookieSecure() as $secure) {
                foreach ($this->DataProviderCookieHttp() as $http) {
                    foreach ($this->DataProviderCookieSameSite() as $sameSite) {
                            yield array_merge($handlerArgs, $cookie, [$secure, $http, $sameSite]);
                        }
                    }
                }
            }
        }
    }

    public function DataProviderHttpHandlerInstances() : Generator
    {
        yield from [
            [
                HttpHandler::class,
                [
                ],
                'https://example.com/',
                realpath(__DIR__ . '/fixtures'),
                [
                    DaftSource::class => [
                    ],
                ],
            ],
        ];
    }

    public function DataProviderCookieNameValue() : Generator
    {
        yield from [
            [
                'a',
                'b',
            ],
        ];
    }

    public function DataProviderCookieSecure() : Generator
    {
        yield from ['0', '1'];
    }

    public function DataProviderCookieHttp() : Generator
    {
        yield from ['0', '1'];
    }

    public function DataProviderCookieSameSite() : Generator
    {
        yield from ['lax', 'strict'];
    }
}
