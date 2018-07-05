<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework;

use InvalidArgumentException;
use SignpostMarv\DaftRouter\DaftSource;
use SignpostMarv\DaftRouter\Router\Compiler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
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

        $this->routerCacheFile = (string) ((array) $config[DaftSource::class])['cacheFile'];

        /**
        * @var string[] $sources
        */
        $sources = (array) ((array) $config[DaftSource::class])['sources'];

        $this->routerSources = array_values(array_filter($sources, 'is_string'));
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

        return $dispatcher->handle(
            $request,
            (string) parse_url($this->ObtainBaseUrl(), PHP_URL_PATH)
        );
    }

    public function AttachToEventDispatcher(EventDispatcher $dispatcher) : void
    {
        $dispatcher->addListener(KernelEvents::REQUEST, function (GetResponseEvent $e) : void {
            if ( ! $e->hasResponse()) {
                $e->setResponse($this->handle($e->getRequest()));
            }
        });
    }

    protected function ValidateConfig(array $config) : array
    {
        /**
        * @var array|null
        */
        $subConfig = $config[DaftSource::class] ?? null;

        if ( ! is_array($subConfig) || ! isset($subConfig['cacheFile'], $subConfig['sources'])) {
            throw new InvalidArgumentException(sprintf('%s config not found!', DaftSource::class));
        }

        $this->ValidateDaftSourceSubConfig($subConfig);

        return parent::ValidateConfig($config);
    }

    protected function ValidateDaftSourceSubConfig(array $subConfig) : void
    {
        if ( ! is_string($subConfig['cacheFile'])) {
            throw new InvalidArgumentException(sprintf(self::ERROR_SOURCE_CONFIG, 'cacheFile'));
        } elseif ( ! is_array($subConfig['sources'])) {
            throw new InvalidArgumentException(sprintf(self::ERROR_SOURCE_CONFIG, 'sources'));
        } elseif ( ! $this->FileIsUnderBasePath($subConfig['cacheFile'], false)) {
            throw new InvalidArgumentException(self::ERROR_ROUTER_CACHE_FILE_PATH);
        }
    }
}
