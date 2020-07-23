<?php
declare(strict_types = 1);

namespace HomeCli\Commands;

use HomeCli\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListenTelegram extends CommandAbstract
{
    public static $commandName = 'listen:telegram';

    protected function configure()
    {
        $this->setDescription('Слушает команды из телеграмм канала и выполняет их');
        $this->addOption('token', 't', InputOption::VALUE_OPTIONAL, 'Токен для подлючения к телеграму');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::create();

        if (!$config->get('telegram.token') && $input->getOption('token')) {
            $config->set('telegram.token', $input->getOption('token'));
            $config->save();
        }

        if (!$config->get('telegram.token')) {
            $output->writeln('Нет токена для подключения');
            return Command::FAILURE;
        }

        return parent::execute($input, $output);
    }
}