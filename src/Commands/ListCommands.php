<?php
declare(strict_types = 1);

namespace HomeCli\Commands;

use HomeCli\Config;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommands extends CommandAbstract
{
    public static $commandName = 'list';

    protected function configure()
    {
        $this->setDescription('Показать список всех доступных команнд');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::create();

        $output->writeln('Список доступных команд от ' . $config->get('cli.name', 'Rusbios'));

        $commands = scandir(__DIR__);

        $table = new Table($output);

        $table->setHeaders(['Команда', 'Описание']);

        foreach ($commands as $command) {
            $class = 'HomeCli\\Commands\\' . substr($command, 0, -4);

            if (class_exists($class) && $class::$commandName) {
                /** @var CommandAbstract $cli */
                $cli = new $class();
                $table->addRow([$class::$commandName, $cli->getDescription()]);
            }

        }

        $table->render();

        return 0;
    }
}