<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests;

use Generator;
use InvalidArgumentException;
use SignpostMarv\DaftFramework\HttpHandler;
use SignpostMarv\DaftRouter\DaftSource;
use SignpostMarv\DaftRouter\Tests\ImplementationTest as Base;
use Symfony\Component\HttpFoundation\Request;

class HttpHandlerTest extends Base
{
    public function DataProviderHttpHandlerInstances() : Generator
    {
        yield from [
            [
                HttpHandler::class,
                [
                    'ConfigureDatabaseConnection' => [
                        'sqlite::memory:',
                        null,
                        null,
                        [],
                    ],
                ],
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
        foreach ($this->DataProviderHttpHandlerInstances() as $args) {
            list($implementation, $postConstructionCalls, $baseUrl, $basePath, $config) = $args;

            foreach ($this->DataProviderVerifyHandlerGood() as $testArgs) {
                list(
                    $sources,
                    $prefix,
                    $expectedStatus,
                    $expectedContent,
                    $requestArgs
                ) = $testArgs;

                list($uri) = $requestArgs;

                $parsed = parse_url($uri);

                $baseUrl = $parsed['scheme'] . '://' . $parsed['host'];

                if (isset($parsed['port'])) {
                    $baseUrl .= ':' . $parsed['port'];
                }

                $baseUrl .= '/' . $prefix;

                $config[DaftSource::class]['sources'] = $sources;
                $config[DaftSource::class]['cacheFile'] = (
                    __DIR__ .
                    '/fixtures/http-kernel.fast-route.cache'
                );

                if (is_file($config[DaftSource::class]['cacheFile'])) {
                    unlink($config[DaftSource::class]['cacheFile']);
                }

                $instance = Utilities::ObtainHttpHandlerInstance(
                    $this,
                    $implementation,
                    $baseUrl,
                    $basePath,
                    $config
                );
                Utilities::ConfigureFrameworkInstance($this, $instance, $args[1]);

                $request = Request::create(
                    ...$requestArgs
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
    ) : void {
        $response = $instance->handle($request);

        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame($expectedContent, $response->getContent());
    }

    public function DataProviderTestDroppedConfigProperty() : Generator
    {
        foreach ($this->DataProviderHttpHandlerInstances() as $args) {
            list($implementation, $postConstructionCalls, $baseUrl, $basePath, $config) = $args;

            foreach ($this->DataProviderVerifyHandlerGood() as $testArgs) {
                list(
                    $sources,
                    $prefix,
                    $expectedStatus,
                    $expectedContent,
                    $requestArgs
                ) = $testArgs;

                list($uri) = $requestArgs;

                $parsed = parse_url($uri);

                $baseUrl = $parsed['scheme'] . '://' . $parsed['host'];

                if (isset($parsed['port'])) {
                    $baseUrl .= ':' . $parsed['port'];
                }

                $baseUrl .= '/' . $prefix;

                $config[DaftSource::class]['sources'] = $sources;
                $config[DaftSource::class]['cacheFile'] = (
                    __DIR__ .
                    '/fixtures/http-kernel.fast-route.cache'
                );

                if (is_file($config[DaftSource::class]['cacheFile'])) {
                    unlink($config[DaftSource::class]['cacheFile']);
                }

                foreach (
                    [
                        'cacheFile',
                        'sources',
                    ] as $omitSubProperty
                ) {
                    $modifiedConfig = $config;

                    unset($modifiedConfig[DaftSource::class][$omitSubProperty]);

                    $args1 = $args[1];

                    yield [$implementation, $baseUrl, $basePath, $modifiedConfig, $args1];
                }
            }
        }
    }

    /**
    * @dataProvider DataProviderTestDroppedConfigProperty
    */
    public function testDroppedConfigProperty(
        string $implementation,
        string $baseUrl,
        string $basePath,
        array $config,
        array $args1
    ) : void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('%s config not found!', DaftSource::class));

        $instance = Utilities::ObtainHttpHandlerInstance(
            $this,
            $implementation,
            $baseUrl,
            $basePath,
            $config
        );
        Utilities::ConfigureFrameworkInstance($this, $instance, $args1);
    }
}
