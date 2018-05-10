<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests;

use Generator;
use SignpostMarv\DaftFramework\Symfony\HttpKernel\HttpKernel;
use SignpostMarv\DaftRouter\DaftSource;
use SignpostMarv\DaftRouter\Tests\ImplementationTest as Base;
use Symfony\Component\HttpFoundation\Request;

class HttpKernelTest extends Base
{
    public function DataProviderHttpKernelInstances() : Generator
    {
        yield from [
            [
                HttpKernel::class,
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

    public function DataProviderHttpKernelHandle() : Generator
    {
        foreach ($this->DataProviderHttpKernelInstances() as $args) {
            list($implementation, $postConstructionCalls, $baseUrl, $basePath, $config) = $args;

            foreach ($this->DataProviderVerifyHandlerGood() as $testArgs) {
                list(
                    $sources,
                    $prefix,
                    $expectedStatus,
                    $expectedContent,
                    $uri,
                    $method,
                    $parameters,
                    $cookies,
                    $files,
                    $server,
                    $content
                ) = $testArgs;

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

                $instance = Utilities::ObtainHttpKernelInstance(
                    $this,
                    $implementation,
                    $baseUrl,
                    $basePath,
                    $config
                );
                Utilities::ConfigureFrameworkInstance($this, $instance, $args[1]);

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
    * @dataProvider DataProviderHttpKernelHandle
    */
    public function testHandlerGoodOnHttpKernel(
        HttpKernel $instance,
        Request $request,
        int $expectedStatus,
        string $expectedContent
    ) : void {
        $response = $instance->handle($request);

        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame($expectedContent, $response->getContent());
    }
}
