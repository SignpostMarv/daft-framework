<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests;

use Generator;
use InvalidArgumentException;
use RuntimeException;
use SignpostMarv\DaftFramework\HttpHandler;
use SignpostMarv\DaftRouter\DaftSource;
use SignpostMarv\DaftRouter\Tests\ImplementationTest as Base;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;

class HttpHandlerTest extends Base
{
    public function DataProviderHttpHandlerInstances() : Generator
    {
        yield from [
            [
                HttpHandler::class,
                ['ConfigureDatabaseConnection' => ['sqlite::memory:', null, null, []]],
                'https://example.com/',
                realpath(__DIR__ . '/fixtures'),
                [
                    DaftSource::class => [
                        'cacheFile' => (__DIR__ . '/fixtures/http-kernel.fast-route.cache'),
                    ],
                ],
            ],
        ];
    }

    public function DataProviderHttpHandlerHandle() : Generator
    {
        /**
        * @var iterable<array>
        */
        $argsSources = $this->DataProviderHttpHandlerInstances();

        foreach ($argsSources as $args) {
            /**
            * @var string
            */
            $implementation = $args[0];

            /**
            * @var array<string, mixed[]>
            */
            $postConstructionCalls = $args[1];

            /**
            * @var string
            */
            $baseUrl = $args[2];

            /**
            * @var string
            */
            $basePath = $args[3];

            /**
            * @var array<string, mixed[]>
            */
            $config = $args[4];

            /**
            * @var iterable<array>
            */
            $testArgsSources = $this->DataProviderVerifyHandlerGood();

            foreach ($testArgsSources as $testArgs) {
                list($baseUrl, $config, $testArgs) = $this->prepDataProviderVerifyHandlerGoodArgs(
                    $baseUrl,
                    $config,
                    $testArgs
                );

                if (
                    ! is_array($testArgs) ||
                    ! isset($testArgs[0], $testArgs[1], $testArgs[2], $testArgs[3], $testArgs[4])
                ) {
                    throw new RuntimeException(sprintf(
                        'Unsupported args derived from %s::prepDataProviderVerifyHandlerGoodArgs',
                        get_class($this)
                    ));
                }

                $baseUrl = (string) $baseUrl;
                $config = (array) $config;

                /**
                * @var array
                */
                $testArgs = $testArgs;

                /**
                * @var string
                */
                $sources = $testArgs[0];
                /**
                * @var string
                */
                $prefix = $testArgs[1];
                /**
                * @var int
                */
                $expectedStatus = $testArgs[2];
                /**
                * @var string
                */
                $expectedContent = $testArgs[3];
                /**
                * @var array
                */
                $requestArgs = $testArgs[4];

                $instance = Utilities::ObtainHttpHandlerInstanceMixedArgs(
                    $this,
                    $implementation,
                    $baseUrl,
                    $basePath,
                    $config
                );
                Utilities::ConfigureFrameworkInstance($this, $instance, $postConstructionCalls);

                $uri = (string) $requestArgs[0];
                $method = (string) ($requestArgs[1] ?? 'GET');
                $parameters = (array) ($requestArgs[2] ?? []);
                $cookies = (array) ($requestArgs[3] ?? []);
                $files = (array) ($requestArgs[4] ?? []);
                $server = (array) ($requestArgs[5] ?? []);

                /**
                * @var string|resource|null
                */
                $content = ($requestArgs[6] ?? null);

                $request = Request::create(
                    $uri,
                    $method,
                    $parameters,
                    $cookies,
                    $files,
                    $server,
                    $content
                );

                yield [$instance, $request, $expectedStatus, $expectedContent];
            }
        }
    }

    /**
    * @dataProvider DataProviderHttpHandlerHandle
    */
    public function testHandlerGoodOnHttpHandler(
        HttpHandler $instance,
        Request $request,
        int $expectedStatus,
        string $expectedContent
    ) {
        $response = $instance->handle($request);

        static::assertSame($expectedStatus, $response->getStatusCode());
        static::assertSame($expectedContent, $response->getContent());
    }

    public function DataProviderTestDroppedConfigProperty() : Generator
    {
        /**
        * @var iterable<array>
        */
        $argsSources = $this->DataProviderHttpHandlerInstances();

        foreach ($argsSources as $args) {
            list($implementation, $postConstructionCalls, $baseUrl, $basePath, $config) = $args;

            /**
            * @var iterable<array>
            */
            $testArgsSources = $this->DataProviderVerifyHandlerGood();

            foreach ($testArgsSources as $testArgs) {
                list($baseUrl, $config, $testArgs) = $this->prepDataProviderVerifyHandlerGoodArgs(
                    (string) $baseUrl,
                    (array) $config,
                    $testArgs
                );
                list(
                    $sources,
                    $prefix,
                    $expectedStatus,
                    $expectedContent,
                    $requestArgs
                ) = (array) $testArgs;

                foreach (['cacheFile', 'sources'] as $omitSubProperty) {
                    /**
                    * @var array<string, mixed>
                    */
                    $modifiedConfig = (array) $config;

                    /**
                    * @var array<string, mixed>
                    */
                    $modifiedDaftSourceConfig = $modifiedConfig[DaftSource::class];

                    unset($modifiedDaftSourceConfig[$omitSubProperty]);

                    $modifiedConfig[DaftSource::class] = $modifiedDaftSourceConfig;

                    /**
                    * @var array<string, mixed[]>
                    */
                    $args1 = $args[1];

                    yield [$implementation, $baseUrl, $basePath, $modifiedConfig, $args1];
                }
            }
        }
    }

    /**
    * @param array<string, mixed[]> $args1
    *
    * @dataProvider DataProviderTestDroppedConfigProperty
    */
    public function testDroppedConfigProperty(
        string $implementation,
        string $baseUrl,
        string $basePath,
        array $config,
        array $args1
    ) {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('%s config not found!', DaftSource::class));

        $instance = Utilities::ObtainHttpHandlerInstanceMixedArgs(
            $this,
            $implementation,
            $baseUrl,
            $basePath,
            $config
        );
        Utilities::ConfigureFrameworkInstance($this, $instance, $args1);
    }

    /**
    * @depends testHandlerGoodOnHttpHandler
    *
    * @dataProvider DataProviderHttpHandlerHandle
    */
    public function testHandlerGoodWithHttpKernel(
        HttpHandler $instance,
        Request $request,
        int $expectedStatus,
        string $expectedContent
    ) {
        $dispatcher = new EventDispatcher();
        $instance->AttachToEventDispatcher($dispatcher);

        $args = [
            $dispatcher,
            new ControllerResolver(),
            new RequestStack(),
        ];

        if (class_exists(ArgumentResolver::class)) {
            $args[] = new ArgumentResolver();
        }

        $kernel = new HttpKernel(
            ...$args
        );

        /**
        * @var \Symfony\Component\HttpFoundation\Response
        */
        $response = $kernel->handle($request);

        $kernel->terminate($request, $response);

        static::assertSame($expectedStatus, $response->getStatusCode());
        static::assertSame($expectedContent, $response->getContent());
    }

    protected function prepDataProviderVerifyHandlerGoodArgs(
        string $baseUrl,
        array $config,
        array $testArgs
    ) : array {
        list($sources, $prefix, $expectedStatus, $expectedContent, $requestArgs) = $testArgs;

        list($uri) = (array) $requestArgs;

        $parsed = parse_url((string) $uri);

        $baseUrl = (string) $parsed['scheme'] . '://' . (string) $parsed['host'];

        if (isset($parsed['port'])) {
            $baseUrl .= ':' . (string) ((int) $parsed['port']);
        }

        $baseUrl .= '/' . (string) $prefix;

        /**
        * @var array<string, string|array<int, stirng>>
        * @var array<int, string> $daftSourceConfig['sources']
        * @var string $daftSourceConfig['cacheFile']
        */
        $daftSourceConfig = (array) $config[DaftSource::class];

        $daftSourceConfig['sources'] = array_filter((array) $sources, 'is_string');
        $daftSourceConfig['cacheFile'] = __DIR__ . '/fixtures/http-kernel.fast-route.cache';

        if (is_file($daftSourceConfig['cacheFile'])) {
            unlink((string) ($daftSourceConfig['cacheFile']));
        }

        $config[DaftSource::class] = $daftSourceConfig;

        return [$baseUrl, $config, $testArgs];
    }
}
