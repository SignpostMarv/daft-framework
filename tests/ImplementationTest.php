<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests;

use BadMethodCallException;
use Generator;
use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\TestCase as Base;
use SignpostMarv\DaftFramework\Framework;
use SignpostMarv\DaftFramework\HttpHandler;
use SignpostMarv\DaftRouter\DaftSource;
use SignpostMarv\DaftRouter\Tests\Fixtures\Config as DaftRouterFixturesConfig;
use Symfony\Component\HttpFoundation\Request;

class ImplementationTest extends Base
{
    public function tearDown()
    {
        foreach (
            array_filter(
                array_filter([
                    realpath(__DIR__ . '/fixtures/http-kernel.fast-route.cache'),
                ], 'is_string'),
                'is_file'
            ) as $cleanup
        ) {
            unlink($cleanup);
        }
    }

    public function DataProviderGoodSources() : Generator
    {
        yield from [
            [
                Framework::class,
                [],
                'https://example.com/',
                realpath(__DIR__ . '/fixtures'),
                [],
            ],
            [
                Framework::class,
                [],
                'https://example.com:8080/',
                realpath(__DIR__ . '/fixtures'),
                [],
            ],
            [
                Framework::class,
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
                [],
            ],
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
                        'sources' => [
                            DaftRouterFixturesConfig::class,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function DataProviderBadSources() : Generator
    {
        yield from [
            [
                Framework::class,
                InvalidArgumentException::class,
                'Base path must be a directory!',
                null,
                [],
                'https://example.com/',
                __FILE__,
                [],
            ],
            [
                Framework::class,
                InvalidArgumentException::class,
                'Path should be explicitly set to via realpath!',
                null,
                [],
                'https://example.com/',
                (__DIR__ . '/fixtures/'),
                [],
            ],
            [
                HttpHandler::class,
                InvalidArgumentException::class,
                sprintf(
                    '%s config not found!',
                    DaftSource::class
                ),
                null,
                [],
                'https://example.com/',
                realpath(__DIR__ . '/fixtures'),
                [],
            ],
            [
                HttpHandler::class,
                InvalidArgumentException::class,
                sprintf(
                    '%s config does not specify "%s" correctly.',
                    DaftSource::class,
                    'cacheFile'
                ),
                null,
                [],
                'https://example.com/',
                realpath(__DIR__ . '/fixtures'),
                [
                    DaftSource::class => [
                        'cacheFile' => false,
                        'sources' => false,
                    ],
                ],
            ],
            [
                HttpHandler::class,
                InvalidArgumentException::class,
                sprintf(
                    '%s config does not specify "%s" correctly.',
                    DaftSource::class,
                    'sources'
                ),
                null,
                [],
                'https://example.com/',
                realpath(__DIR__ . '/fixtures'),
                [
                    DaftSource::class => [
                        'cacheFile' => (__DIR__ . '/fixtures/http-kernel.fast-route.cache'),
                        'sources' => false,
                    ],
                ],
            ],
            [
                HttpHandler::class,
                InvalidArgumentException::class,
                sprintf(
                    '%s config property cacheFile does not exist under the framework base path.',
                    DaftSource::class
                ),
                null,
                [],
                'https://example.com/',
                realpath(__DIR__ . '/fixtures'),
                [
                    DaftSource::class => [
                        'cacheFile' => __FILE__,
                        'sources' => [],
                    ],
                ],
            ],
        ];
    }

    public function DataProviderGoodSourcesSansDatabaseConnection() : Generator
    {
        foreach ($this->DataProviderGoodSources() as $args) {
            if ( ! isset($args[1]['ConfigureDatabaseConnection'])) {
                yield $args;
            }
        }
    }

    public function DataProviderGoodSourcesWithDatabaseConnection() : Generator
    {
        foreach ($this->DataProviderGoodSources() as $args) {
            if (isset($args[1]['ConfigureDatabaseConnection'])) {
                yield $args;
            }
        }
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    *
    * @dataProvider DataProviderGoodSources
    */
    public function testEverythingInitialisesFine(
        string $implementation,
        array $postConstructionCalls,
        string $baseUrl,
        string $basePath,
        array $config = []
    ) : void {
        $instance = $this->ObtainFrameworkInstance($implementation, $baseUrl, $basePath, $config);
        $this->ConfigureFrameworkInstance($instance, $postConstructionCalls);

        $this->assertSame($baseUrl, $instance->ObtainBaseUrl());
        $this->assertSame($basePath, $instance->ObtainBasePath());
        $this->assertSame($config, $instance->ObtainConfig());

        if (isset($postConstructionCalls['ConfigureDatabaseConnection'])) {
            $this->assertInstanceOf(EasyDB::class, $instance->ObtainDatabaseConnection());
        }
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    *
    * @dataProvider DataProviderBadSources
    *
    * @depends testEverythingInitialisesFine
    */
    public function testThingsFail(
        string $implementation,
        string $expectedExceptionClass,
        ? string $expectedExceptionMessage,
        ? int $expectedExceptionCode,
        array $postConstructionCalls,
        string $baseUrl,
        string $basePath,
        array $config = []
    ) : void {
        $this->expectException($expectedExceptionClass);
        if (is_string($expectedExceptionMessage)) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }
        if (is_int($expectedExceptionCode)) {
            $this->expectExceptionCode($expectedExceptionCode);
        }

        $instance = $this->ObtainFrameworkInstance($implementation, $baseUrl, $basePath, $config);
        $this->ConfigureFrameworkInstance($instance, $postConstructionCalls);
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    *
    * @dataProvider DataProviderGoodSourcesSansDatabaseConnection
    *
    * @depends testEverythingInitialisesFine
    */
    public function testGoodSourcesSansDatabaseConnection(
        string $implementation,
        array $postConstructionCalls,
        string $baseUrl,
        string $basePath,
        array $config = []
    ) : void {
        $instance = $this->ObtainFrameworkInstance($implementation, $baseUrl, $basePath, $config);
        $this->ConfigureFrameworkInstance($instance, $postConstructionCalls);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Database Connection not available!');

        $instance->ObtainDatabaseConnection();
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    *
    * @dataProvider DataProviderGoodSourcesWithDatabaseConnection
    *
    * @depends testEverythingInitialisesFine
    */
    public function testGoodSourcesWithDatabaseConnection(
        string $implementation,
        array $postConstructionCalls,
        string $baseUrl,
        string $basePath,
        array $config = []
    ) : void {
        $instance = $this->ObtainFrameworkInstance($implementation, $baseUrl, $basePath, $config);
        $this->ConfigureFrameworkInstance($instance, $postConstructionCalls);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Database Connection already made!');

        $instance->ConfigureDatabaseConnection(
            ...($postConstructionCalls['ConfigureDatabaseConnection'])
        );
    }

    /**
    * @dataProvider DataProviderGoodSources
    *
    * @depends testEverythingInitialisesFine
    */
    public function testUnpairedFrameworksFail(string $implementation) : void
    {
        $this->assertTrue(is_a($implementation, Framework::class, true));

        $this->assertFalse(Request::createFromGlobals() === Request::createFromGlobals());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'No framework instance has been paired with the provided request!'
        );

        $implementation::ObtainFrameworkForRequest(Request::createFromGlobals());
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    *
    * @dataProvider DataProviderGoodSources
    *
    * @depends testEverythingInitialisesFine
    * @depends testUnpairedFrameworksFail
    */
    public function testDisposeOfFrameworkReferences(
        string $implementation,
        array $postConstructionCalls,
        string $baseUrl,
        string $basePath,
        array $config = []
    ) : void {
        list($instance, $requestA, $requestB) = $this->PrepareReferenceDisposalTest(
            $implementation,
            $postConstructionCalls,
            $baseUrl,
            $basePath,
            $config,
            Request::createFromGlobals(),
            Request::createFromGlobals()
        );

        $implementation::DisposeOfFrameworkReferences($instance);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'No framework instance has been paired with the provided request!'
        );

        $implementation::ObtainFrameworkForRequest($requestA);
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    *
    * @dataProvider DataProviderGoodSources
    *
    * @depends testEverythingInitialisesFine
    * @depends testUnpairedFrameworksFail
    */
    public function testDisposeOfRequestReferences(
        string $implementation,
        array $postConstructionCalls,
        string $baseUrl,
        string $basePath,
        array $config = []
    ) : void {
        list($instance, $requestA, $requestB) = $this->PrepareReferenceDisposalTest(
            $implementation,
            $postConstructionCalls,
            $baseUrl,
            $basePath,
            $config,
            Request::createFromGlobals(),
            Request::createFromGlobals()
        );

        $implementation::DisposeOfFrameworkReferences($instance);
        $implementation::DisposeOfRequestReferences($requestA);
        $implementation::PairWithRequest($instance, $requestB);

        $this->assertSame($instance, $implementation::ObtainFrameworkForRequest($requestB));

        $implementation::DisposeOfRequestReferences($requestB);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'No framework instance has been paired with the provided request!'
        );

        $implementation::ObtainFrameworkForRequest($requestB);
    }

    protected function PrepareReferenceDisposalTest(
        string $implementation,
        array $postConstructionCalls,
        string $baseUrl,
        string $basePath,
        array $config,
        Request $requestA,
        Request $requestB
    ) : array {
        $instance = $this->ObtainFrameworkInstance(
            $implementation,
            $baseUrl,
            $basePath,
            $config
        );
        $this->ConfigureFrameworkInstance($instance, $postConstructionCalls);

        $requestA = Request::createFromGlobals();
        $requestB = Request::createFromGlobals();

        $implementation::PairWithRequest($instance, $requestA);
        $implementation::PairWithRequest($instance, $requestB);

        $this->assertSame($instance, $implementation::ObtainFrameworkForRequest($requestA));
        $this->assertSame($instance, $implementation::ObtainFrameworkForRequest($requestB));

        return [$instance, $requestA, $requestB];
    }

    protected function ObtainFrameworkInstance(
        string $implementation,
        string $baseUrl,
        string $basePath,
        array $config = []
    ) : Framework {
        return Utilities::ObtainFrameworkInstance(
            $this,
            $implementation,
            $baseUrl,
            $basePath,
            $config
        );
    }

    protected function ConfigureFrameworkInstance(
        Framework $instance,
        array $postConstructionCalls
    ) : void {
        Utilities::ConfigureFrameworkInstance($this, $instance, $postConstructionCalls);
    }
}
