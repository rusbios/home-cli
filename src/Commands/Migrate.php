<?php
declare(strict_types = 1);

namespace RB\Cli\Commands;

use Migrations\FileQueue;
use RB\Cli\Config;
use RB\Cli\Exceptions\ConnectException;
use RB\DB\Builder\DB;
use RB\DB\Builder\QueryBuilder;
use RB\DB\Connects\DBConnetcInterface;
use RB\DB\Connects\MySQLConnect;
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
        $db = $this->createConnectDB();
        DB::setConnect($db);
        QueryBuilder::setConnect($db);
        Run::add(FileQueue::class);
        Run::applay($db);

        return parent::execute($input, $output);
    }

    /**
     * @return DBConnetcInterface
     * @throws ConnectException
     */
    protected function createConnectDB(): DBConnetcInterface
    {
        $config = Config::create();

        if (!$config->get('queue.db.dbname')) {
            throw new ConnectException('DB config not found');
        }

        if ($config->get('queue.db.type') == 'mysql') {
            return new MySQLConnect(
                $config->get('queue.db.host'),
                $config->get('queue.db.dbname'),
                $config->get('queue.db.user'),
                $config->get('queue.db.password'),
                $config->get('queue.db.port')
            );
        }
    }
}