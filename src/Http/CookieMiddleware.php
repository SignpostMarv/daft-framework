<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Http;

use SignpostMarv\DaftFramework\Framework;
use SignpostMarv\DaftRouter\DaftRequestInterceptor;
use SignpostMarv\DaftRouter\DaftResponseModifier;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieMiddleware implements DaftRequestInterceptor, DaftResponseModifier
{
    public static function DaftRouterMiddlewareModifier(
        Request $request,
        Response $response
    ) : Response {
        /**
        * @var Response
        */
        $response = static::OmNomNom($request, $response);

        return $response;
    }

    /**
    * @return Response|null
    */
    public static function DaftRouterMiddlewareHandler(
        Request $request,
        Response $response = null
    ) {
        return static::OmNomNom($request, $response);
    }

    public static function DaftRouterRoutePrefixRequirements() : array
    {
        return [
            '/',
        ];
    }

    public static function DaftRouterRoutePrefixExceptions() : array
    {
        return [];
    }

    public static function PerhapsReconfigureResponseCookies(
        Response $response,
        bool $configSecure,
        bool $configHttpOnly,
        string $configSameSite = null
    ) {
        foreach ($response->headers->getCookies() as $cookie) {
            self::PerhapsReconfigureCookie(
                $response,
                $cookie,
                $configSecure,
                $configHttpOnly,
                $configSameSite
            );
        }
    }

    public static function PerhapsReconfigureCookie(
        Response $response,
        Cookie $cookie,
        bool $isSecure,
        bool $isHttpOnly,
        string $sameSite = null
    ) {
        $updateSecure = $cookie->isSecure() !== $isSecure;
        $updateHttpOnly = $cookie->isHttpOnly() !== $isHttpOnly;

        $updateSameSite = false;
        if (method_exists($cookie, 'getSameSite')) {
            $updateSameSite = $cookie->getSameSite() !== $sameSite;
        }

        if ($updateSecure || $updateHttpOnly || $updateSameSite) {
            static::ReconfigureCookie($response, $cookie, $isSecure, $isHttpOnly, $sameSite);
        }
    }

    public static function ReconfigureCookie(
        Response $response,
        Cookie $cookie,
        bool $configSecure,
        bool $configHttpOnly,
        string $configSameSite = null
    ) {
        $cookieName = $cookie->getName();
        $cookiePath = $cookie->getPath();
        $cookieDomain = $cookie->getDomain();
        $response->headers->removeCookie($cookieName, $cookiePath, $cookieDomain);
        $args = [
            $cookieName,
            $cookie->getValue(),
            $cookie->getExpiresTime(),
            $cookiePath,
            $cookieDomain,
            $configSecure,
            $configHttpOnly,
        ];
        if (method_exists($cookie, 'getSameSite')) {
            $args[] = (bool) $cookie->isRaw();
            $args[] = $configSameSite;
        }
        $response->headers->setCookie(new Cookie(
            ...$args
        ));
    }

    /**
    * @return Response|null
    */
    protected static function OmNomNom(Request $request, Response $response = null)
    {
        $config = Framework::ObtainFrameworkForRequest($request)->ObtainConfig();
        if (isset($response, $config[self::class])) {
            $config = (array) $config[self::class];

            /**
            * @var string|null
            */
            $sameSite = $config['sameSite'] ?? null;
            $sameSite = is_string($sameSite) ? $sameSite : null;
            $isSecure = (bool) ($config['secure'] ?? null);
            $isHttpOnly = (bool) ($config['httpOnly'] ?? null);

            self::PerhapsReconfigureResponseCookies($response, $isSecure, $isHttpOnly, $sameSite);
        }

        return $response;
    }
}
