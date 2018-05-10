<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Symfony\Console;

use BadMethodCallException;
use SignpostMarv\DaftFramework\AttachDaftFramework;
use SignpostMarv\DaftFramework\Framework;
use SignpostMarv\DaftFramework\Symfony\Console\Command\Command;
use SignpostMarv\DaftInterfaceCollector\StaticMethodCollector;
use Symfony\Component\Console\Application as Base;
use Symfony\Component\Console\Command\Command as BaseCommand;

class Application extends Base
{
    use AttachDaftFramework;

    public function add(BaseCommand $command)
    {
        return $this->addStrict($command);
    }

    public function addStrict(BaseCommand $command) : ? BaseCommand
    {
        $out = parent::add($command);

        if ($out instanceof Command) {
            $maybeFramework = $this->GetDaftFramework();

            if ( ! ($maybeFramework instanceof Framework)) {
                throw new BadMethodCallException(
                    'Cannot add a daft framework command without a framework being attached!'
                );
            }

            $out->AttachDaftFramework($maybeFramework);
        } elseif ($command instanceof Command) {
            $command->DetachDaftFramework();
        }

        return $out;
    }

    /**
    * @return static
    */
    public static function CollectApplicationsWithCommands(
        string $name,
        string $version,
        Framework $framework
    ) : self {
        $application = new static($name, $version);
        $application->AttachDaftFramework($framework);

        $collector = new StaticMethodCollector(
            [
                DaftConsoleSource::class => [
                    'DaftFrameworkConsoleSources' => [
                        DaftConsoleSource::class,
                        BaseCommand::class,
                        Command::class,
                    ],
                ],
            ],
            [
                BaseCommand::class,
                Command::class,
            ]
        );

        $config = ($framework->ObtainConfig()[DaftConsoleSource::class] ?? []);

        foreach (
            $collector->Collect(
                ...array_values(is_array($config) ? $config : [])
            ) as $implementation
        ) {
            if (is_a($implementation, BaseCommand::class, true)) {
                /**
                * @var BaseCommand $command
                */
                $command = new $implementation($implementation::getDefaultName());

                $application->add($command);
            }
        }

        return $application;
    }
}
