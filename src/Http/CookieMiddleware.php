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
        * @var Response $response
        */
        $response = static::OmNomNom($request, $response);

        return $response;
    }

    public static function DaftRouterMiddlewareHandler(
        Request $request,
        ? Response $response
    ) : ? Response {
        return static::OmNomNom($request, $response);
    }

    public static function DaftRouterRoutePrefixRequirements() : array
    {
        return [];
    }

    public static function DaftRouterRoutePrefixExceptions() : array
    {
        return [];
    }

    public static function PerhapsReconfigureResponseCookies(
        Response $response,
        bool $configSecure,
        bool $configHttpOnly,
        ? string $configSameSite
    ) : void {
        /**
        * @var Cookie $cookie
        */
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
        ? string $sameSite
    ) : void {
        $updateSecure = $cookie->isSecure() !== $isSecure;
        $updateHttpOnly = $cookie->isHttpOnly() !== $isHttpOnly;
        $updateSameSite = $cookie->getSameSite() !== $sameSite;

        if ($updateSecure || $updateHttpOnly || $updateSameSite) {
            static::ReconfigureCookie($response, $cookie, $isSecure, $isHttpOnly, $sameSite);
        }
    }

    public static function ReconfigureCookie(
        Response $response,
        Cookie $cookie,
        bool $configSecure,
        bool $configHttpOnly,
        ? string $configSameSite
    ) : void {
        $cookieName = $cookie->getName();
        $cookiePath = $cookie->getPath();
        $cookieDomain = $cookie->getDomain();
        $response->headers->removeCookie($cookieName, $cookiePath, $cookieDomain);
        $response->headers->setCookie(new Cookie(
            $cookieName,
            $cookie->getValue(),
            $cookie->getExpiresTime(),
            $cookiePath,
            $cookieDomain,
            $configSecure,
            $configHttpOnly,
            $cookie->isRaw(),
            $configSameSite
        ));
    }

    protected static function OmNomNom(Request $request, ? Response $response) : ? Response
    {
        $config = Framework::ObtainFrameworkForRequest($request)->ObtainConfig();
        if (isset($response, $config[self::class])) {
            $config = (array) $config[self::class];

            /**
            * @var string|null $sameSite
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
