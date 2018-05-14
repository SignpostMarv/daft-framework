<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SignpostMarv\DaftFramework\Framework;
use SignpostMarv\DaftFramework\HttpHandler;

class Utilities
{
    public static function ObtainFrameworkInstance(
        TestCase $testCase,
        string $implementation,
        ...$implementationArgs
    ) : Framework {
        $testCase->assertTrue(
            is_a($implementation, Framework::class, true),
            sprintf(
                'Argument %u passed to %s must be an implementation of %s',
                1,
                __METHOD__,
                Framework::class
            )
        );

        return new $implementation(...$implementationArgs);
    }

    public static function ObtainHttpHandlerInstance(
        TestCase $testCase,
        string $implementation,
        ...$implementationArgs
    ) : HttpHandler {
        $testCase->assertTrue(
            is_a($implementation, HttpHandler::class, true),
            sprintf(
                'Argument %u passed to %s must be an implementation of %s',
                1,
                __METHOD__,
                HttpHandler::class
            )
        );

        /**
        * @var HttpHandler $instance
        */
        $instance = static::ObtainFrameworkInstance(
            $testCase,
            $implementation,
            ...$implementationArgs
        );

        return $instance;
    }

    public static function ConfigureFrameworkInstance(
        TestCase $testCase,
        Framework $instance,
        array $postConstructionCalls
    ) : void {
        if (count($postConstructionCalls) > 0) {
            $reflector = new ReflectionClass($instance);

            foreach (array_keys($postConstructionCalls) as $method) {
                $testCase->assertTrue(method_exists($instance, $method), sprintf(
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
