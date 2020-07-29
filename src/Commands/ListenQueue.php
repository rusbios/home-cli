<?php
declare(strict_types = 1);

namespace RB\HomeCli\Commands;

use Exception;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use RB\DB\Builder\DB;
use RB\DB\Builder\PDOConnect;
use RB\HomeCli\Config;
use RB\HomeCli\Models\FileQueueModel;
use RB\HomeCli\Services\DownloadFile;
use RB\HomeCli\Services\UploadFile;
use Symfony\Component\Console\Output\OutputInterface;

class ListenQueue extends CommandAbstract
{
    public static $commandName = 'listen:queue';

    protected function configure()
    {
        $this->setDescription('Слушает команды из телеграмм канала и выполняет их');
        $this->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Тип базы данных');
        $this->addOption('host', 'h', InputOption::VALUE_OPTIONAL, 'Хост базы данных');
        $this->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Порт базы данных');
        $this->addOption('dbname', null, InputOption::VALUE_OPTIONAL, 'Имя базы данных');
        $this->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'login базы данных');
        $this->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'password базы данных');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createConnectDB($input);

        while (true) {
            foreach (FileQueueModel::getDownList() as $file) {
                (new DownloadFile($file))->processing();
            }

            foreach (FileQueueModel::getUpList() as $file) {
                (new UploadFile($file))->processing();
            }
            sleep(1);
        }

        return parent::execute($input, $output);
    }

    /**
     * @param InputInterface $input
     * @throws Exception
     */
    protected function createConnectDB(InputInterface $input): void
    {
        $config = Config::create();

        $saveConfig = false;
        foreach (['type', 'host', 'port', 'dbname', 'user', 'password'] as $param) {
            if ($input->getOption($param)) {
                $config->set('queueDB.'.$param, $input->getOption($param));
                $saveConfig = true;
            }
        }
        if ($saveConfig) {
            $config->save();
        }

        if (!$config->get('queueDB.dbname')) {
            throw new Exception('DB config not found');
        }

        $connect = new PDOConnect(
            $config->get('queueDB.type'),
            $config->get('queueDB.host'),
            $config->get('queueDB.dbname'),
            $config->get('queueDB.user'),
            $config->get('queueDB.password'),
            $config->get('queueDB.port')
        );

        DB::setConnect($connect);
    }
}