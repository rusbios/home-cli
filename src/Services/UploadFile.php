<?php
declare(strict_types = 1);

namespace RB\HomeCli\Services;

use RB\HomeCli\Models\FileQueueModel;

class UploadFile
{
    protected FileQueueModel $fileQueue;

    public function __construct(FileQueueModel $fileQueue)
    {
        $this->fileQueue = $fileQueue;
    }

    public function processing(): void
    {
        // TODO начать стримить файлы в отдельных прцессах, сразу все что есть в очереди, с возможностью ограничить количество стримов
    }
}