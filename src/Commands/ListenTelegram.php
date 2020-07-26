<?php
declare(strict_types = 1);

namespace RB\HomeCli\Commands;

use RB\HomeCli\Config;
use RB\HomeCli\Services\ChatBot;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TelegramBot\Api\BotApi;

class ListenTelegram extends CommandAbstract
{
    public static $commandName = 'listen:telegram';

    protected function configure()
    {
        $this->setDescription('Слушает команды из телеграмм канала и выполняет их');
        $this->addOption('token', 't', InputOption::VALUE_OPTIONAL, 'Токен для подлючения к телеграму');
        $this->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'Пароль для авторизации пользователя в телеграмме');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::create();

        if (!$config->get('telegram.token') && $input->getOption('token')) {
            $config->set('telegram.token', $input->getOption('token'));
            $config->save();
        }

        if ($input->getOption('password')) {
            $config->set('telegram.accessPas', $input->getOption('password'));
            $config->save();
        }

        if (!$config->get('telegram.token')) {
            $output->writeln('Нет токена для подключения');
            return Command::FAILURE;
        }

        $telegramBot = new BotApi($config->get('telegram.token'));
        $chatBot = new ChatBot($telegramBot, $config);

        $last = $config->get('telegram.update.last', 0);

        while (true) {
            try {
                foreach ($telegramBot->getUpdates($last) as $update) {
                    $message = $update->getMessage() ?? $update->getEditedMessage();
                    $output->writeln('send message: ' . $message->getText());
                    $answerMessage = $chatBot->run(
                        $message->getText(),
                        $message->getChat()->getUsername()
                    );
                    $output->writeln('answer message: ' . $answerMessage);
                    $telegramBot->sendMessage(
                        $message->getChat()->getId(),
                        $answerMessage
                    );

                    $last = $update->getUpdateId() + 1;
                }
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
            }

            $config->set('telegram.update.last', $last);
            $config->save();
        }

        return parent::execute($input, $output);
    }
}