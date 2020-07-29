<?php
declare(strict_types = 1);

namespace RB\Cli;

use RB\Cli\Commands\CommandAbstract;
use RB\Cli\Exceptions\CommandNameException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class Run
{
    public function preload(): void
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        $this->loadProject();
    }

    /**
     * @param string $name
     * @param array $args
     * @throws CommandNameException
     */
    public function command(string $name, array $args): void
    {
        $commandClass = $this->commandNameToClass(trim($name));

        /** @var CommandAbstract $command */
        $command = new $commandClass();

        $input = new ArgvInput($args);

        $command->run($input, new ConsoleOutput());
    }

    /**
     * @param string $commandName
     * @return string
     * @throws CommandNameException
     */
    protected function commandNameToClass(string $commandName): string
    {
        $commandFiles = scandir(__DIR__ . '/Commands/');

        foreach ($commandFiles as $fileName) {
            /** @var CommandAbstract $class */
            $class = 'RB\\Cli\\Commands\\' . substr($fileName, 0, -4);

            if (class_exists($class) && $class::$commandName == $commandName) {
                return (string)$class;
            }
        }

        throw new CommandNameException('Command not found');
    }

    /**
     * @param string $path
     */
    protected function loadProject(string $path = '/'): void
    {
        $dirs = scandir(__DIR__ . $path);

        foreach ($dirs as $dir) {
            if (strlen($dir) <= 2 || $dir == 'configs') {
                continue;
            }

            if (substr($dir, -4, 4) == '.php') {
                include_once __DIR__ . $path . $dir;
                continue;
            }

            $this->loadProject($path . $dir . '/');
        }
    }
}