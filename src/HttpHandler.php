<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework;

use InvalidArgumentException;
use SignpostMarv\DaftRouter\DaftSource;
use SignpostMarv\DaftRouter\Router\Compiler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpHandler extends Framework
{
    const ERROR_SOURCE_CONFIG = DaftSource::class . ' config does not specify "%s" correctly.';

    const ERROR_ROUTER_CACHE_FILE_PATH =
        DaftSource::class .
        ' config property cacheFile does not exist under the framework base path.';

    /**
    * @var string
    */
    private $routerCacheFile;

    /**
    * @var array<int, string>
    */
    private $routerSources;

    public function __construct(string $baseUrl, string $basePath, array $config = [])
    {
        parent::__construct($baseUrl, $basePath, $config);

        $this->routerCacheFile = $config[DaftSource::class]['cacheFile'];

        $this->routerSources = array_values(array_filter(
            $config[DaftSource::class]['sources'],
            'is_string'
        ));
    }

    public function handle(Request $request) : Response
    {
        self::PairWithRequest($this, $request);

        $dispatcher = Compiler::ObtainDispatcher(
            [
                'cacheFile' => $this->routerCacheFile,
            ],
            ...$this->routerSources
        );

        return $dispatcher->handle($request, parse_url($this->ObtainBaseUrl(), PHP_URL_PATH));
    }

    protected function ValidateConfig(array $config) : array
    {
        if (
            ! isset(
                $config[DaftSource::class],
                $config[DaftSource::class]['cacheFile'],
                $config[DaftSource::class]['sources']
            )
        ) {
            throw new InvalidArgumentException(sprintf('%s config not found!', DaftSource::class));
        } elseif ( ! is_string($config[DaftSource::class]['cacheFile'])) {
            throw new InvalidArgumentException(sprintf(self::ERROR_SOURCE_CONFIG, 'cacheFile'));
        } elseif ( ! is_array($config[DaftSource::class]['sources'])) {
            throw new InvalidArgumentException(sprintf(self::ERROR_SOURCE_CONFIG, 'sources'));
        } elseif ( ! $this->FileIsUnderBasePath($config[DaftSource::class]['cacheFile'], false)) {
            throw new InvalidArgumentException(self::ERROR_ROUTER_CACHE_FILE_PATH);
        }

        return parent::ValidateConfig($config);
    }
}