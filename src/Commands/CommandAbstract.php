<?php
declare(strict_types = 1);

namespace RB\Cli\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class CommandAbstract extends Command
{
    /** @var string */
    public static $commandName;

    public function __construct()
    {
        parent::__construct(static::$commandName);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Done!');
        return Command::SUCCESS;
    }
}