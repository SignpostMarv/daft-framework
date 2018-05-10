<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Tests;

use BadMethodCallException;
use Generator;
use PHPUnit\Framework\TestCase as Base;
use SignpostMarv\DaftFramework\Framework;
use SignpostMarv\DaftFramework\Symfony\Console\Application;
use SignpostMarv\DaftFramework\Symfony\Console\Command\Command;
use SignpostMarv\DaftFramework\Tests\fixtures\Console\Command\TestCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;

class ApplicationTest extends Base
{
    final public function DataProviderConsoleApplicationConfigFiltered() : Generator
    {
        foreach ($this->DataProviderConsoleApplicationConfig() as $args) {
            if (6 === count($args) && is_string($args[0] ?? null) && is_file($args[0])) {
                $configFile = array_shift($args);
                end($args);
                $args[key($args)][] = (array) include($configFile);

                yield array_values($args);
            }
        }
    }

    final public function DataProviderDaftConsoleCommands() : Generator
    {
        foreach ($this->DataProviderConsoleApplicationConfigFiltered() as $args) {
            $this->assertTrue(is_a($args[3], Framework::class, true));

            /**
            * @var Framework $framework
            */
            $framework = new $args[3](...$args[4]);

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
    * @dataProvider DataProviderConsoleApplicationConfigFiltered
    */
    public function testApplicationSetup(
        string $name,
        string $version,
        array $expectedCommandInstances,
        string $frameworkImplementation,
        array $frameworkArgs
    ) : void {
        $this->assertTrue(is_a($frameworkImplementation, Framework::class, true));

        /**
        * @var Framework $framework
        */
        $framework = new $frameworkImplementation(...$frameworkArgs);

        $this->assertSame($frameworkArgs[0], $framework->ObtainBaseUrl());
        $this->assertSame($frameworkArgs[1], $framework->ObtainBasePath());
        $this->assertSame($frameworkArgs[2], $framework->ObtainConfig());

        $application = Application::CollectApplicationsWithCommands($name, $version, $framework);

        $this->assertSame($name, $application->getName());
        $this->assertSame($version, $application->getVersion());

        $commands = array_map('get_class', $application->all());

        foreach ($expectedCommandInstances as $expectedComamnd) {
            $this->assertTrue(class_exists($expectedComamnd));
            $this->assertContains($expectedComamnd, $commands);
        }

        $failingApplication = new Application($name, $version);

        $this->assertSame($name, $application->getName());
        $this->assertSame($version, $application->getVersion());

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
    * @dataProvider DataProviderDaftConsoleCommands
    */
    public function testCommandFrameworkAttachment(Framework $framework, Command $command) : void
    {
        $this->assertFalse($command->CheckIfUsingFrameworkInstance($framework));

        $command->AttachDaftFramework($framework);

        $this->assertTrue($command->CheckIfUsingFrameworkInstance($framework));

        $this->assertSame($framework, $command->DetachDaftFramework());

        $this->assertFalse($command->CheckIfUsingFrameworkInstance($framework));

        $command->AttachDaftFramework($framework);

        $this->assertTrue($command->CheckIfUsingFrameworkInstance($framework));

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Framework must not be attached if a framework is already attached!'
        );

        $command->AttachDaftFramework($framework);
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
