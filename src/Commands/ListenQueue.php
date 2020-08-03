<?php
declare(strict_types = 1);

namespace RB\Cli\Commands;

use Symfony\Component\Console\Input\{InputInterface, InputOption};
use RB\Cli\Services\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use RB\Cli\Exceptions\ConnectException;
use RB\Cli\Config;
use RB\Cli\Models\FileQueueModel;
use RB\Cli\Services\FileService;
use RB\DB\Builder\{DB, PDOConnect};
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

        $this->addOption('ftp_type', null, InputOption::VALUE_OPTIONAL, 'ftp или ftps');
        $this->addOption('ftp_host', null, InputOption::VALUE_OPTIONAL, 'Хост ftp соединения');
        $this->addOption('ftp_port', null, InputOption::VALUE_OPTIONAL, 'Порт ftp соединения');
        $this->addOption('ftp_login', null, InputOption::VALUE_OPTIONAL, 'login ftp соединения');
        $this->addOption('ftp_pass', null, InputOption::VALUE_OPTIONAL, 'password ftp соединения');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createConnectDB($input);

        $ftpConnect = $this->createConnectFtp($input);

        $fileService = new FileService($ftpConnect, new Logger('queue_files'));

        while (true) {
            $countProcess = $ftpConnect->countActiveProcess();

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
                $config->set('queueDB.'.$param, $input->getOption($param));
                $saveConfig = true;
            }
        }
        if ($saveConfig) {
            $config->save();
        }

        if (!$config->get('queueDB.dbname')) {
            throw new ConnectException('DB config not found');
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
        foreach (['ftp_type', 'ftp_host', 'ftp_port', 'ftp_login', 'ftp_pass'] as $param) {
            if ($input->getOption($param)) {
                $config->set('queueFtp.'.$param, $input->getOption($param));
                $saveConfig = true;
            }
        }
        if ($saveConfig) {
            $config->save();
        }

        if (!$config->get('queueFtp.ftp_host')) {
            throw new ConnectException('FTP config not found');
        }

        return new FtpClient(
            $config->get('queueFtp.ftp_host'),
            $config->get('queueFtp.ftp_login'),
            $config->get('queueFtp.ftp_pass'),
            $config->get('queueFtp.ftp_port', 21),
            $config->get('queueFtp.ftp_timeout', 90),
            $config->get('queueFtp.ftp_type', 'ftp') == 'ftps'
        );
    }
}