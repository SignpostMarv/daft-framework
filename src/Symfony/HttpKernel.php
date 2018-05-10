<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Symfony\HttpKernel;

use InvalidArgumentException;
use SignpostMarv\DaftFramework\Framework;
use SignpostMarv\DaftRouter\DaftSource;
use SignpostMarv\DaftRouter\Router\Compiler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HttpKernel extends Framework implements HttpKernelInterface
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

    public function handle(
        Request $request,
        $type = HttpKernelInterface::MASTER_REQUEST,
        $catch = true
    ) : Response {
        return $this->handleStrict($request, $type, $catch);
    }

    public function handleStrict(
        Request $request,
        int $type = HttpKernelInterface::MASTER_REQUEST,
        bool $catch = true
    ) : Response {
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
        $sourceConfig = $config[DaftSource::class] ?? null;
        if ( ! isset($sourceConfig, $sourceConfig['cacheFile'], $sourceConfig['sources'])) {
            throw new InvalidArgumentException(sprintf('%s config not found!', DaftSource::class));
        } elseif ( ! is_string($sourceConfig['cacheFile'])) {
            throw new InvalidArgumentException(sprintf(self::ERROR_SOURCE_CONFIG, 'cacheFile'));
        } elseif ( ! is_array($sourceConfig['sources'])) {
            throw new InvalidArgumentException(sprintf(self::ERROR_SOURCE_CONFIG, 'sources'));
        } elseif (
            ! $this->FileIsUnderBasePath($sourceConfig['cacheFile'], false)
        ) {
            throw new InvalidArgumentException(self::ERROR_ROUTER_CACHE_FILE_PATH);
        }

        return parent::ValidateConfig($config);
    }
}
