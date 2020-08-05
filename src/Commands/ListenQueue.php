<?php
declare(strict_types = 1);

namespace RB\Cli\Commands;

use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use RB\Cli\Exceptions\ConnectException;
use RB\Cli\Config;
use RB\Cli\Models\FileQueueModel;
use RB\Cli\Services\{FileService, Logger};
use RB\DB\Builder\DB;
use RB\DB\Connects\MySQLConnect;
use RB\Transport\Exceptions\ConnectException as TransportConnectException;
use RB\Transport\FtpClient;

class ListenQueue extends CommandAbstract
{
    public const MAX_PROCESS = 5;

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

        $this->addOption('ftp_ssl', null, InputOption::VALUE_OPTIONAL, 'ftps соединения');
        $this->addOption('ftp_host', null, InputOption::VALUE_OPTIONAL, 'Хост ftp соединения');
        $this->addOption('ftp_port', null, InputOption::VALUE_OPTIONAL, 'Порт ftp соединения');
        $this->addOption('ftp_login', null, InputOption::VALUE_OPTIONAL, 'login ftp соединения');
        $this->addOption('ftp_password', null, InputOption::VALUE_OPTIONAL, 'password ftp соединения');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createConnectDB($input);

        $fileService = new FileService($this->createConnectFtp($input), new Logger('queue_files'));

        while (true) {
            $countProcess = $fileService->countActiveProcess();

            if ($countProcess >= self::MAX_PROCESS) {
                continue;
            }

            foreach (FileQueueModel::getUpList(self::MAX_PROCESS - $countProcess) as $file) {
                $fileService->upProcessing($file);
                ++$countProcess;
            }

            if ($countProcess >= self::MAX_PROCESS) {
                continue;
            }

            foreach (FileQueueModel::getDownList(self::MAX_PROCESS - $countProcess) as $file) {
                $fileService->downProcessing($file);
                ++$countProcess;
            }
        }

        return parent::execute($input, $output);
    }

    /**
     * @param InputInterface $input
     * @throws ConnectException
     */
    protected function createConnectDB(InputInterface $input): void
    {
        $config = Config::create();

        $saveConfig = false;
        foreach (['type', 'host', 'port', 'dbname', 'user', 'password'] as $param) {
            if ($input->getOption($param)) {
                $config->set('queue.db.'.$param, $input->getOption($param));
                $saveConfig = true;
            }
        }
        if ($saveConfig) {
            $config->save();
        }

        if (!$config->get('queue.db.dbname')) {
            throw new ConnectException('DB config not found');
        }

        if ($config->get('queue.db.type') == 'mysql') {
            $connect = new MySQLConnect(
                $config->get('queue.db.host'),
                $config->get('queue.db.dbname'),
                $config->get('queue.db.user'),
                $config->get('queue.db.password'),
                $config->get('queue.db.port')
            );
        }

        DB::setConnect($connect);
    }

    /**
     * @param InputInterface $input
     * @return FtpClient
     * @throws ConnectException
     * @throws TransportConnectException
     */
    protected function createConnectFtp(InputInterface $input): FtpClient
    {
        $config = Config::create();

        $saveConfig = false;
        foreach (['host', 'port', 'login', 'password', 'ssl'] as $param) {
            if ($input->getOption("ftp_$param")) {
                $config->set('queue.ftp.'.$param, $input->getOption("ftp_$param"));
                $saveConfig = true;
            }
        }
        if ($saveConfig) {
            $config->save();
        }

        if (!$config->get('queue.ftp.host')) {
            throw new ConnectException('FTP config not found');
        }

        return new FtpClient(
            $config->get('queue.ftp.host'),
            $config->get('queue.ftp.login'),
            $config->get('queue.ftp.password'),
            (int)$config->get('queue.ftp.port', 21),
            (int)$config->get('queue.ftp.timeout', 90),
            (bool)$config->get('queue.ftp.ssl', false)
        );
    }
}