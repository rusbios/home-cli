<?php
declare(strict_types = 1);

namespace RB\Cli\Commands;

use Migrations\FileQueue;
use RB\Cli\Config;
use RB\Cli\Exceptions\ConnectException;
use RB\DB\DBConnect;
use RB\DB\Exceptions\ConnectException as DBConnectException;
use RB\DB\Migrate\Run;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate extends CommandAbstract
{
    public static $commandName = 'migrate';

    protected function configure()
    {
        $this->setDescription('Виполнить миграции');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createConnectDB();
        Run::add(FileQueue::class);

        return parent::execute($input, $output);
    }

    /**
     * @throws ConnectException
     * @throws DBConnectException
     */
    protected function createConnectDB(): void
    {
        $config = Config::create();

        if (!$config->get('queue.db.dbname')) {
            throw new ConnectException('DB config not found');
        }

        DBConnect::created($config->get('queue.db.type'), 'default', [
            'host' => $config->get('queue.db.host'),
            'dbName' => $config->get('queue.db.dbname'),
            'user' => $config->get('queue.db.user'),
            'password' => $config->get('queue.db.password'),
            'port' => $config->get('queue.db.port'),
            'path' => $config->get('queue.db.path'), // todo add config
        ]);
    }
}