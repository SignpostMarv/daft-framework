<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use SignpostMarv\DaftFramework\Framework;
use SignpostMarv\DaftFramework\HttpHandler;

class Utilities
{
    /**
    * @param mixed ...$implementationArgs
    */
    public static function ObtainFrameworkInstanceMixedArgs(
        TestCase $testCase,
        string $implementation,
        ...$implementationArgs
    ) : Framework {
        $testCase::assertIsString($implementationArgs[0] ?? null);
        $testCase::assertIsString($implementationArgs[1] ?? null);
        $testCase::assertIsArray($implementationArgs[2] ?? null);

        /**
        * @var array{0:string, 1:string, 2:array}
        */
        $implementationArgs = $implementationArgs;

        list($baseUrl, $basePath, $config) = $implementationArgs;
        $implementationArgs = array_slice($implementationArgs, 3);

        return static::ObtainFrameworkInstance(
            $testCase,
            $implementation,
            $baseUrl,
            $basePath,
            $config,
            ...$implementationArgs
        );
    }

    /**
    * @param mixed ...$implementationArgs
    */
    public static function ObtainFrameworkInstance(
        TestCase $testCase,
        string $implementation,
        string $baseUrl,
        string $basePath,
        array $config,
        ...$implementationArgs
    ) : Framework {
        if ( ! is_a($implementation, Framework::class, true)) {
            $testCase::assertTrue(
                is_a($implementation, Framework::class, true),
                sprintf(
                    'Argument %u passed to %s must be an implementation of %s',
                    1,
                    __METHOD__,
                    Framework::class
                )
            );

            throw new RuntimeException('unreachable line here');
        }

        /**
        * @var Framework
        */
        $out = new $implementation($baseUrl, $basePath, $config, ...$implementationArgs);

        return $out;
    }

    /**
    * @param mixed ...$implementationArgs
    */
    public static function ObtainHttpHandlerInstanceMixedArgs(
        TestCase $testCase,
        string $implementation,
        ...$implementationArgs
    ) : HttpHandler {
        $testCase::assertIsString($implementationArgs[0] ?? null);
        $testCase::assertIsString($implementationArgs[1] ?? null);
        $testCase::assertIsArray($implementationArgs[2] ?? null);

        /**
        * @var array{0:string, 1:string, 2:array}
        */
        $implementationArgs = $implementationArgs;

        list($baseUrl, $basePath, $config) = $implementationArgs;
        $implementationArgs = array_slice($implementationArgs, 3);

        return static::ObtainHttpHandlerInstance(
            $testCase,
            $implementation,
            $baseUrl,
            $basePath,
            $config,
            ...$implementationArgs
        );
    }

    /**
    * @param mixed ...$implementationArgs
    */
    public static function ObtainHttpHandlerInstance(
        TestCase $testCase,
        string $implementation,
        string $baseUrl,
        string $basePath,
        array $config,
        ...$implementationArgs
    ) : HttpHandler {
        $testCase::assertTrue(
            is_a($implementation, HttpHandler::class, true),
            sprintf(
                'Argument %u passed to %s must be an implementation of %s',
                1,
                __METHOD__,
                HttpHandler::class
            )
        );

        /**
        * @var HttpHandler
        */
        $instance = static::ObtainFrameworkInstanceMixedArgs(
            $testCase,
            $implementation,
            $baseUrl,
            $basePath,
            $config,
            ...$implementationArgs
        );

        return $instance;
    }

    /**
    * @param array<string, mixed[]> $postConstructionCalls
    */
    public static function ConfigureFrameworkInstance(
        TestCase $testCase,
        Framework $instance,
        array $postConstructionCalls
    ) : void {
        if (count($postConstructionCalls) > 0) {
            foreach (array_keys($postConstructionCalls) as $method) {
                $testCase::assertTrue(method_exists($instance, $method), sprintf(
                    'Argument %u passed to %s must contain keys referring to methods on %s',
                    2,
                    __METHOD__,
                    get_class($instance)
                ));

                $instance->$method(...($postConstructionCalls[$method]));
            }
        }
    }
}
