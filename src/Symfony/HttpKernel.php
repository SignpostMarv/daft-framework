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

        if (
            ! isset(
                $config[DaftSource::class],
                $config[DaftSource::class]['cacheFile'],
                $config[DaftSource::class]['sources']
            )
        ) {
            throw new InvalidArgumentException(sprintf('%s config not found!', DaftSource::class));
        } elseif ( ! is_string($config[DaftSource::class]['cacheFile'])) {
            throw new InvalidArgumentException(sprintf(
                '%s config does not specify "%s" correctly.',
                DaftSource::class,
                'cacheFile'
            ));
        } elseif ( ! is_array($config[DaftSource::class]['sources'])) {
            throw new InvalidArgumentException(sprintf(
                '%s config does not specify "%s" correctly.',
                DaftSource::class,
                'sources'
            ));
        } elseif (
            file_exists($config[DaftSource::class]['cacheFile']) &&
            ! $this->FileIsUnderBasePath($config[DaftSource::class]['cacheFile'])
        ) {
            throw new InvalidArgumentException(sprintf(
                '%s config property cacheFile does not exist under the framework base path.',
                DaftSource::class
            ));
        }

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
}
