<?php
declare(strict_types = 1);

namespace RB\Cli\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Status extends CommandAbstract
{
    public static $commandName = 'stat:all';

    protected function configure(): void
    {
        $this->setDescription('Состояние сервера');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('stats');
        return parent::execute($input, $output);
    }
}