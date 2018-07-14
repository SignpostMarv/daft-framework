<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests;

use BadMethodCallException;
use Generator;
use PHPUnit\Framework\TestCase as Base;
use ReflectionMethod;
use SignpostMarv\DaftFramework\Framework;
use SignpostMarv\DaftFramework\Symfony\Console\Application;
use SignpostMarv\DaftFramework\Symfony\Console\Command\Command;
use SignpostMarv\DaftFramework\Symfony\Console\Command\FastRouteCacheCommand;
use SignpostMarv\DaftFramework\Symfony\Console\DaftConsoleSource;
use SignpostMarv\DaftFramework\Tests\fixtures\Console\Command\ExecuteCoverageCommand;
use SignpostMarv\DaftFramework\Tests\fixtures\Console\Command\TestCommand;
use SignpostMarv\DaftRouter\DaftSource;
use SignpostMarv\DaftRouter\Tests\Fixtures\Config;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Tester\CommandTester;

class ApplicationTest extends Base
{
    const NUM_EXPECTED_ARGS = 6;

    public function __construct(string $name = '', array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->backupGlobals = false;
        $this->backupStaticAttributes = false;
        $this->runTestInSeparateProcess = false;
    }

    /**
    * @psalm-suppress UnresolvableInclude
    */
    final public function DataProviderConsoleApplicationConfigFiltered() : Generator
    {
        /**
        * @var array<int, mixed> $args
        */
        foreach ($this->DataProviderConsoleApplicationConfig() as $args) {
            if (
                self::NUM_EXPECTED_ARGS === count($args) &&
                is_string($args[0]) &&
                is_file($args[0])
            ) {
                $configFile = (string) array_shift($args);
                end($args);
                $key = (int) key($args);
                $appendTo = (array) $args[$key];
                $appendTo[] = (array) include($configFile);
                $args[$key] = $appendTo;

                yield array_values($args);
            }
        }
    }

    final public function DataProviderDaftConsoleCommands() : Generator
    {
        /**
        * @var array<int, mixed> $args
        * @var string $args[3]
        */
        foreach ($this->DataProviderConsoleApplicationConfigFiltered() as $args) {
            static::assertTrue(is_a($args[3], Framework::class, true));

            /**
            * @var Framework $framework
            */
            $framework = new $args[3](...$args[4]);

            /**
            * @var string|null $maybeCommand
            */
            foreach (($args[2] ?? []) as $maybeCommand) {
                if (is_string($maybeCommand) && is_a($maybeCommand, Command::class, true)) {
                    /**
                    * @var Command $command
                    */
                    $command = new $maybeCommand($maybeCommand::getDefaultName());

                    yield [$framework, $command];
                }
            }
        }
    }

    /**
    * @param array<int, string> $expectedCommandInstances
    *
    * @dataProvider DataProviderConsoleApplicationConfigFiltered
    */
    public function testApplicationSetup(
        string $name,
        string $version,
        array $expectedCommandInstances,
        string $frameworkImplementation,
        array $frameworkArgs
    ) : void {
        static::assertTrue(is_a($frameworkImplementation, Framework::class, true));

        /**
        * @var Framework $framework
        */
        $framework = new $frameworkImplementation(...$frameworkArgs);

        static::assertSame($frameworkArgs[0], $framework->ObtainBaseUrl());
        static::assertSame($frameworkArgs[1], $framework->ObtainBasePath());
        static::assertSame($frameworkArgs[2], $framework->ObtainConfig());

        $application = Application::CollectApplicationWithCommands($name, $version, $framework);

        static::assertSame($name, $application->getName());
        static::assertSame($version, $application->getVersion());

        $commands = array_map('get_class', $application->all());

        foreach ($expectedCommandInstances as $expectedComamnd) {
            static::assertTrue(class_exists($expectedComamnd));
            static::assertContains($expectedComamnd, $commands);
        }

        $constructedApplication = new Application($name, $version);

        $constructedApplication->AttachDaftFramework($framework);

        $constructedApplication->CollectCommands(...$expectedCommandInstances);

        $commands = array_map('get_class', $application->all());

        foreach ($expectedCommandInstances as $expectedComamnd) {
            static::assertTrue(class_exists($expectedComamnd));
            static::assertContains($expectedComamnd, $commands);
        }

        $failingApplication = new Application($name, $version);

        static::assertSame($name, $application->getName());
        static::assertSame($version, $application->getVersion());

        foreach ($expectedCommandInstances as $expectedComamnd) {
            /**
            * @var BaseCommand $command
            */
            $command = new $expectedComamnd($expectedComamnd::getDefaultName());

            $this->expectException(BadMethodCallException::class);
            $this->expectExceptionMessage(
                'Cannot add a daft framework command without a framework being attached!'
            );

            $failingApplication->add($command);
        }
    }

    /**
    * @param array<int, string> $expectedCommandInstances
    *
    * @dataProvider DataProviderConsoleApplicationConfigFiltered
    *
    * @depends testApplicationSetup
    */
    public function testCommandCollectionWithoutFramework(
        string $name,
        string $version,
        array $expectedCommandInstances,
        string $frameworkImplementation,
        array $frameworkArgs
    ) : void {
        $constructedApplication = new Application($name, $version);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Cannot collect commands without an attached framework instance!'
        );

        $constructedApplication->CollectCommands(...$expectedCommandInstances);
    }

    /**
    * @dataProvider DataProviderDaftConsoleCommands
    *
    * @depends testApplicationSetup
    */
    public function testCommandFrameworkAttachment(Framework $framework, Command $command) : void
    {
        static::assertFalse($command->CheckIfUsingFrameworkInstance($framework));

        $command->AttachDaftFramework($framework);

        static::assertTrue($command->CheckIfUsingFrameworkInstance($framework));

        static::assertSame($framework, $command->DetachDaftFramework());

        static::assertFalse($command->CheckIfUsingFrameworkInstance($framework));

        $command->AttachDaftFramework($framework);

        static::assertTrue($command->CheckIfUsingFrameworkInstance($framework));

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Framework must not be attached if a framework is already attached!'
        );

        $command->AttachDaftFramework($framework);
    }

    public function DataProviderFastRouteCacheComamnd() : Generator
    {
        $expectedOutput = file_get_contents(
            __DIR__ .
            '/fixtures/here-is-one-i-made-earlier.fast-route.cache'
        );

        /**
        * @var array $args
        * @var string $args[0]
        * @var string $args[1]
        * @var string $args[3]
        * @var array<int, array<string, mixed>> $args[4]
        */
        foreach ($this->DataProviderConsoleApplicationConfigFiltered() as $args) {
            $frameworkImplementation = $args[3];

            /**
            * @var array<string, array> $args42
            */
            $args42 = (array) ((array) $args[4])[2];
            $args42[DaftConsoleSource::class][] = FastRouteCacheCommand::class;
            $args42[DaftSource::class]['sources'] = [Config::class];
            $args4 = (array) $args[4];
            $args4[2] = $args42;

            $args[4] = $args4;

            /**
            * @var Framework $framework
            */
            $framework = new $frameworkImplementation(...$args[4]);

            $application = Application::CollectApplicationWithCommands(
                $args[0],
                $args[1],
                $framework
            );

            yield [$application, $expectedOutput];
        }
    }

    /**
    * @dataProvider DataProviderFastRouteCacheComamnd
    */
    public function testFastRouteCacheCommand(
        Application $application,
        string $expectedOutput
    ) : void {
        $command = new FastRouteCacheCommand();

        $ref = new ReflectionMethod($command, 'configure');
        $ref->setAccessible(true);

        $command->getDefinition()->setDefinition([]);

        $ref->invoke($command);

        static::assertSame(
            'Update the cache used by the daft framework router',
            $command->getDescription()
        );

        static::assertSame(
            [
                'sources' => [
                    'name' => 'sources',
                    'required' => true,
                    'array' => true,
                    'default' => [],
                    'description' => 'class names for sources',
                ],
            ],
            array_map(
                function (InputArgument $arg) : array {
                    return [
                        'name' => $arg->getName(),
                        'required' => $arg->isRequired(),
                        'array' => $arg->isArray(),
                        'default' => $arg->getDefault(),
                        'description' => $arg->getDescription(),
                    ];
                },
                $command->getDefinition()->getArguments()
            )
        );

        // ref: https://stackoverflow.com/questions/47183273/test-command-symfony-with-phpunit

        $command = $application->find('daft-framework:router:update-cache');

        static::assertInstanceOf(FastRouteCacheCommand::class, $command);

        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [
                'sources' => [
                    Config::class,
                ],
            ],
            [
                'command' => $command->getName(),
            ]
        );

        static::assertSame($expectedOutput, $commandTester->getDisplay());
    }

    /**
    * @dataProvider DataProviderFastRouteCacheComamnd
    */
    public function testExecuteCoverageCommand(Application $application) : void
    {
        $command = $application->find('test:execute-coverage');

        static::assertInstanceOf(ExecuteCoverageCommand::class, $command);

        $commandTester = new CommandTester($command);

        $result = $commandTester->execute(
            [
                'sources' => [
                    Config::class,
                ],
            ],
            [
                'command' => $command->getName(),
            ]
        );

        static::assertSame(1, $result);
        static::assertSame(
            ('could not get temporary filename!' . PHP_EOL),
            $commandTester->getDisplay()
        );
    }

    protected function DataProviderConsoleApplicationConfig() : Generator
    {
        yield from [
            [
                __DIR__ . '/fixtures/config.php',
                'Test',
                '0.0.0',
                [
                    TestCommand::class,
                ],
                Framework::class,
                [
                    'https://example.com/',
                    realpath(__DIR__ . '/fixtures'),
                ],
            ],
        ];
    }
}
