<?php
declare(strict_types = 1);

namespace RB\Cli\Services;

use Exception;
use Psr\Log\LoggerInterface;
use RB\Cli\Models\FileQueueModel;
use RB\DB\Exceptions\{OperatorException, PropertyException};
use RB\Transport\Exceptions\AsyncException;
use RB\Transport\FtpClient;

class FileService
{
    protected FtpClient $ftpClient;
    protected LoggerInterface $logger;

    private array $process = [];

    /**
     * FileService constructor.
     * @param FtpClient $ftpClient
     * @param LoggerInterface $logger
     */
    public function __construct(FtpClient $ftpClient, LoggerInterface $logger)
    {
        $this->ftpClient = $ftpClient;
        $this->logger = $logger;
    }

    /**
     * @param FileQueueModel $fileQueue
     * @throws AsyncException
     * @throws OperatorException
     * @throws PropertyException
     */
    public function downProcessing(FileQueueModel $fileQueue): void
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new AsyncException('Failed to spawn child process');
        } elseif ($pid) {
            $this->process[$pid] = $fileQueue->status = FileQueueModel::STATUS_PROGRESS;
            ++$fileQueue->attempts;
            $fileQueue->save();

            try {
                $this->ftpClient->down($fileQueue->file_path);
                $this->process[$pid] = $fileQueue->status = FileQueueModel::STATUS_DONE;
            } catch (Exception $e) {
                $this->process[$pid] = $fileQueue->status = FileQueueModel::STATUS_ERROR;
                $this->logger->error($e->getMessage(), [
                    'queue_id' => $fileQueue->id,
                    'path' => $fileQueue->file_path,
                    'attempts' => $fileQueue->attempts,
                ]);
            }

            $fileQueue->save();
        }
    }

    /**
     * @param FileQueueModel $fileQueue
     * @throws AsyncException
     * @throws OperatorException
     * @throws PropertyException
     */
    public function upProcessing(FileQueueModel $fileQueue): void
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new AsyncException('Failed to spawn child process');
        } elseif ($pid) {
            $this->process[$pid] = $fileQueue->status = FileQueueModel::STATUS_PROGRESS;
            ++$fileQueue->attempts;
            $fileQueue->save();

            try {
                $this->ftpClient->up($fileQueue->file_path);
                $this->process[$pid] = $fileQueue->status = FileQueueModel::STATUS_DONE;
            } catch (Exception $e) {
                $this->process[$pid] = $fileQueue->status = FileQueueModel::STATUS_ERROR;
                $this->logger->error($e->getMessage(), [
                    'queue_id' => $fileQueue->id,
                    'path' => $fileQueue->file_path,
                    'attempts' => $fileQueue->attempts,
                ]);
            }

            $fileQueue->save();
        }
    }

    /**
     * @return int
     */
    public function countActiveProcess(): int
    {
        $count = 0;

        foreach ($this->process as $status) {
            if ($status === FileQueueModel::STATUS_PROGRESS) {
                ++$count;
            }
        }

        return $count;
    }
}