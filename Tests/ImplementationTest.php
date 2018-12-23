<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests;

use BadMethodCallException;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase as Base;
use SignpostMarv\DaftFramework\Framework;
use SignpostMarv\DaftFramework\HttpHandler;
use SignpostMarv\DaftRouter\DaftSource;
use SignpostMarv\DaftRouter\Tests\Fixtures\Config as DaftRouterFixturesConfig;
use Symfony\Component\HttpFoundation\Request;

class ImplementationTest extends Base
{
    public function __construct(string $name = '', array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->backupGlobals = false;
        $this->backupStaticAttributes = false;
        $this->runTestInSeparateProcess = false;
    }

    public function tearDown() : void
    {
        /**
        * @var string[]
        */
        $strings = array_filter(
            [
                realpath(__DIR__ . '/fixtures/http-kernel.fast-route.cache'),
            ],
            'is_string'
        );

        foreach (
            array_filter(
                $strings,
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

        if ((bool) getenv('SCRUTINIZER')) {
            yield [
                Framework::class,
                [
                    'ConfigureDatabaseConnection' => [
                        'mysql:host=localhost;dbname=information_schema',
                        'root',
                        '',
                        [],
                    ],
                ],
                'https://example.com/',
                realpath(__DIR__ . '/fixtures'),
                [],
            ];

            yield [
                HttpHandler::class,
                [
                    'ConfigureDatabaseConnection' => [
                        'mysql:host=localhost;dbname=information_schema',
                        'root',
                        '',
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
            ];
        }
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
        /**
        * @var iterable<array>
        */
        $sources = $this->DataProviderGoodSources();

        foreach ($sources as $args) {
            if ( ! isset($args[1]['ConfigureDatabaseConnection'])) {
                yield $args;
            }
        }
    }

    public function DataProviderGoodSourcesWithDatabaseConnection() : Generator
    {
        /**
        * @var iterable<array>
        */
        $sources = $this->DataProviderGoodSources();

        foreach ($sources as $args) {
            if (isset($args[1]['ConfigureDatabaseConnection'])) {
                yield $args;
            }
        }
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    * @param mixed ...$implementationArgs
    *
    * @dataProvider DataProviderGoodSources
    */
    public function testEverythingInitialisesFine(
        string $implementation,
        array $postConstructionCalls,
        ...$implementationArgs
    ) : Framework {
        $instance = $this->ObtainFrameworkInstance($implementation, ...$implementationArgs);
        $this->ConfigureFrameworkInstance($instance, $postConstructionCalls);

        list($baseUrl, $basePath, $config) = $this->extractDefaultFrameworkArgs(
            $implementationArgs
        );

        static::assertSame($baseUrl, $instance->ObtainBaseUrl());
        static::assertSame($basePath, $instance->ObtainBasePath());
        static::assertSame($config, $instance->ObtainConfig());

        return $instance;
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    * @param mixed ...$implementationArgs
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
        ...$implementationArgs
    ) : void {
        $this->expectException($expectedExceptionClass);
        if (is_string($expectedExceptionMessage)) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }
        if (is_int($expectedExceptionCode)) {
            $this->expectExceptionCode($expectedExceptionCode);
        }

        $instance = $this->ObtainFrameworkInstance($implementation, ...$implementationArgs);
        $this->ConfigureFrameworkInstance($instance, $postConstructionCalls);
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    * @param mixed ...$implementationArgs
    *
    * @dataProvider DataProviderGoodSourcesSansDatabaseConnection
    *
    * @depends testEverythingInitialisesFine
    */
    public function testGoodSourcesSansDatabaseConnection(
        string $implementation,
        array $postConstructionCalls,
        ...$implementationArgs
    ) : void {
        $instance = $this->ObtainFrameworkInstance($implementation, ...$implementationArgs);
        $this->ConfigureFrameworkInstance($instance, $postConstructionCalls);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Database Connection not available!');

        $instance->ObtainDatabaseConnection();
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    * @param mixed ...$implementationArgs
    *
    * @dataProvider DataProviderGoodSourcesWithDatabaseConnection
    *
    * @depends testEverythingInitialisesFine
    */
    public function testGoodSourcesWithDatabaseConnection(
        string $implementation,
        array $postConstructionCalls,
        ...$implementationArgs
    ) : void {
        $instance = $this->ObtainFrameworkInstance($implementation, ...$implementationArgs);
        $this->ConfigureFrameworkInstance($instance, $postConstructionCalls);

        $instance->ObtainDatabaseConnection();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Database Connection already made!');

        /**
        * @var array<int, string|null|array>
        * @var string $configureArgs[0]
        * @var string|null $configureArgs[1]
        * @var string|null $configureArgs[2]
        * @var array $configureArgs[3]
        */
        $configureArgs = $postConstructionCalls['ConfigureDatabaseConnection'];

        $instance->ConfigureDatabaseConnection(
            $configureArgs[0],
            $configureArgs[1] ?? null,
            $configureArgs[2] ?? null,
            $configureArgs[3] ?? []
        );
    }

    /**
    * @dataProvider DataProviderGoodSources
    *
    * @depends testEverythingInitialisesFine
    */
    public function testUnpairedFrameworksFail(string $implementation) : void
    {
        if ( ! is_a($implementation, Framework::class, true)) {
            static::assertTrue(is_a($implementation, Framework::class, true));
        }

        static::assertFalse(Request::createFromGlobals() === Request::createFromGlobals());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'No framework instance has been paired with the provided request!'
        );

        $implementation::ObtainFrameworkForRequest(Request::createFromGlobals());
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    * @param mixed ...$implementationArgs
    *
    * @dataProvider DataProviderGoodSources
    *
    * @depends testEverythingInitialisesFine
    * @depends testUnpairedFrameworksFail
    */
    public function testDisposeOfFrameworkReferences(
        string $implementation,
        array $postConstructionCalls,
        ...$implementationArgs
    ) : void {
        if ( ! is_a($implementation, Framework::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Argument 1 passed to %s must be an implementation of %s, %s given!',
                __METHOD__,
                Framework::class,
                $implementation
            ));
        }

        list($instance, $requestA, $requestB) = $this->PrepareReferenceDisposalTest(
            $implementation,
            $postConstructionCalls,
            Request::createFromGlobals(),
            Request::createFromGlobals(),
            ...$implementationArgs
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
    * @param mixed ...$implementationArgs
    *
    * @dataProvider DataProviderGoodSources
    *
    * @depends testEverythingInitialisesFine
    * @depends testUnpairedFrameworksFail
    */
    public function testDisposeOfRequestReferences(
        string $implementation,
        array $postConstructionCalls,
        ...$implementationArgs
    ) : void {
        if ( ! is_a($implementation, Framework::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Argument 1 passed to %s must be an implementation of %s, %s given!',
                __METHOD__,
                Framework::class,
                $implementation
            ));
        }

        list($instance, $requestA, $requestB) = $this->PrepareReferenceDisposalTest(
            $implementation,
            $postConstructionCalls,
            Request::createFromGlobals(),
            Request::createFromGlobals(),
            ...$implementationArgs
        );

        $implementation::DisposeOfFrameworkReferences($instance);
        $implementation::DisposeOfRequestReferences($requestA);
        $implementation::PairWithRequest($instance, $requestB);

        static::assertSame($instance, $implementation::ObtainFrameworkForRequest($requestB));

        $implementation::DisposeOfRequestReferences($requestB);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'No framework instance has been paired with the provided request!'
        );

        $implementation::ObtainFrameworkForRequest($requestB);
    }

    /**
    * @dataProvider DataProviderGoodSources
    */
    public function testNormaliseUrlFails(string $implementation) : void
    {
        if ( ! is_a($implementation, Framework::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Argument 1 passed to %s must be an implementation of %s, %s given!',
                __METHOD__,
                Framework::class,
                $implementation
            ));
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Base URL must have at least a scheme, host & path in order to be normalised!'
        );

        $implementation::NormaliseUrl('');
    }

    protected function extractDefaultFrameworkArgs(array $implementationArgs) : array
    {
        list($baseUrl, $basePath, $config) = $implementationArgs;

        return [$baseUrl, $basePath, $config];
    }

    /**
    * @param array<string, mixed[]> $postConstructionCalls
    * @param mixed ...$implementationArgs
    */
    protected function PrepareReferenceDisposalTest(
        string $implementation,
        array $postConstructionCalls,
        Request $requestA,
        Request $requestB,
        ...$implementationArgs
    ) : array {
        if ( ! is_a($implementation, Framework::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Argument 1 passed to %s must be an implementation of %s, %s given!',
                __METHOD__,
                Framework::class,
                $implementation
            ));
        }

        $instance = $this->ObtainFrameworkInstance($implementation, ...$implementationArgs);
        $this->ConfigureFrameworkInstance($instance, $postConstructionCalls);

        $requestA = Request::createFromGlobals();
        $requestB = Request::createFromGlobals();

        $implementation::PairWithRequest($instance, $requestA);
        $implementation::PairWithRequest($instance, $requestB);

        static::assertSame($instance, $implementation::ObtainFrameworkForRequest($requestA));
        static::assertSame($instance, $implementation::ObtainFrameworkForRequest($requestB));

        return [$instance, $requestA, $requestB];
    }

    /**
    * @param mixed ...$implementationArgs
    */
    protected function ObtainFrameworkInstance(string $implementation, ...$implementationArgs) : Framework
    {
        return Utilities::ObtainFrameworkInstance($this, $implementation, ...$implementationArgs);
    }

    /**
    * @param array<string, mixed[]> $postConstructionCalls
    */
    protected function ConfigureFrameworkInstance(
        Framework $instance,
        array $postConstructionCalls
    ) : void {
        Utilities::ConfigureFrameworkInstance($this, $instance, $postConstructionCalls);
    }
}
