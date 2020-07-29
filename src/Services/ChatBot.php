<?php
declare(strict_types = 1);

namespace RB\Cli\Services;

use Psr\Log\LoggerInterface;
use RB\Cli\Config;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;

class ChatBot
{
    private const COMMAND_STAT = 'stat';
    private const COMMAND_LIST = 'list';
    private const COMMAND_ACCESS = 'access';
    private const COMMAND_CLI = 'cli';
    private const COMMAND_REG = 'reg';
    private const COMMAND_BOT = 'bot';
    private const COMMAND_ACCESS_CLEAR = 'access-clear';

    private const COMMANDS = [
        self::COMMAND_ACCESS => 'Список авторизованых пользователей',
        self::COMMAND_CLI => 'Выполнить консольную команду на сервере',
        self::COMMAND_LIST => 'Вывод списка доступных команд',
        self::COMMAND_STAT => 'Какаято статистика',
        self::COMMAND_BOT => 'Выполнить команду локального бота',
        self::COMMAND_ACCESS_CLEAR => 'Разавторизовать всех',
    ];

    private array $history;

    private BotApi $botApi;
    private Config $config;
    private LoggerInterface $logger;
    private string $accessPas;
    private array $accessLogins;
    private string $glue = '
';

    /**
     * ChatBot constructor.
     * @param BotApi $botApi
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(BotApi $botApi, Config $config, LoggerInterface $logger)
    {
        $this->botApi = $botApi;
        $this->accessPas = (string)$config->get('telegram.accessPas', 1);
        $this->accessLogins = (array)$config->get('telegram.accessUsers', []);
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param string $message
     * @param string|null $userName
     * @return string
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function run(string $message, string $userName = null): string
    {
        $this->logger->info(($userName ?? 'guest') . ':' . $message);

        if (substr($message, 0, 1) != '/') {
            return 'skip';
        }

        $message = substr($message, 1);
        list($command) = explode(' ', $message);
        $command = mb_strtolower($command);
        $param = trim(str_replace($command, '', $message));

        $this->history[$userName ?? 'guest'][] = $message;

        if ($userName && !in_array($userName, $this->accessLogins)) {
            if ($command == self::COMMAND_REG) {
                if ($this->accessPas == $param) {
                    $this->addAccess($userName);
                    foreach ($this->botApi->getUpdates() as $update) {
                        $message = $update->getMessage() ?: $update->getEditedMessage();
                        if ($message->getChat()->getUsername() == $userName) {
                            $this->botApi->deleteMessage(
                                $message->getChat()->getId(),
                                $message->getMessageId()
                            );
                        }
                    }
                    return 'Добро пожаловать!!!';
                }
                return 'Неправельный пароль';
            }

            return 'Не достаточно прав!!!';
        }

        switch ($command) {
            case self::COMMAND_STAT:
                return 'return statistic';

            case self::COMMAND_LIST:
                $list = 'list commands: '.$this->glue;
                foreach (self::COMMANDS as $command => $description) {
                    $list .= '/'.$command.' : '.$description.$this->glue;
                }
                return $list;

            case self::COMMAND_ACCESS:
                return join($this->glue, $this->accessLogins);

            case self::COMMAND_CLI:
                return shell_exec($param) ?? 'done';

            case self::COMMAND_BOT:
                return shell_exec('php bin/vasya.php ' . $param) ?? 'done';

            case self::COMMAND_ACCESS_CLEAR:
                $this->accessLogins = [];
                return 'До встречи';
        }

        return 'skip';
    }

    /**
     * @param string $userName
     * @return self
     */
    public function addAccess(string $userName): self
    {
        if (!in_array($userName, $this->accessLogins)) {
            $this->accessLogins[] = $userName;

            $this->config->set('telegram.accessUsers', $this->accessLogins);
            $this->config->save();
        }

        return $this;
    }
}