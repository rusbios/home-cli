<?php
declare(strict_types = 1);

namespace RB\Cli\Services;

use RB\Cli\Models\FileQueueModel;

class DownloadFile
{
    protected FileQueueModel $fileQueue;

    public function __construct(FileQueueModel $fileQueue)
    {
        $this->fileQueue = $fileQueue;
    }

    public function processing(): void
    {
        //TODO начать загрузку файлов в отдельном (одном) процессе, возможно в нескольких
    }
}