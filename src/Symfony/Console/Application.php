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

    /**
    * @return BaseCommand|null
    */
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

    public function GetCommandCollector() : StaticMethodCollector
    {
        return new StaticMethodCollector(
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
    }

    public function CollectCommands(string ...$sources) : void
    {
        $framework = $this->GetDaftFramework();

        if ( ! ($framework instanceof Framework)) {
            throw new BadMethodCallException(
                'Cannot collect commands without an attached framework instance!'
            );
        }

        /**
        * @var string
        */
        foreach ($this->GetCommandCollector()->Collect(...$sources) as $implementation) {
            if (is_a($implementation, BaseCommand::class, true)) {
                /**
                * @var BaseCommand
                */
                $command = new $implementation($implementation::getDefaultName());

                $this->add($command);
            }
        }
    }

    /**
    * @return static
    */
    public static function CollectApplicationWithCommands(
        string $name,
        string $version,
        Framework $framework
    ) : self {
        $application = new static($name, $version);
        $application->AttachDaftFramework($framework);

        $config = (array) ($framework->ObtainConfig()[DaftConsoleSource::class] ?? []);

        /**
        * @var string[]
        */
        $sources = array_values(array_filter($config, 'is_string'));

        $application->CollectCommands(...$sources);

        return $application;
    }
}
