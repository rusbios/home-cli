<?php
declare(strict_types = 1);

namespace RB\HomeCli\Models;

use RB\Transport\Db\Model;

/**
 * Class QueueModel
 * @package RB\HomeCli\Models
 *
 * @property int $id
 * @property int $loading
 * @property int $file_id
 * @property int $status
 * @property int $attempts
 * @property string $created_ts //todo datetime
 * @property string $updated_ts
 */
class FileQueueModel extends Model
{
    public const LOADING_DOWN = 1;
    public const LOADING_UP = 2;

    public const LOADING = [
        self::LOADING_DOWN,
        self::LOADING_UP,
    ];

    public const STATUS_NEW = 1;
    public const STATUS_PROGRESS = 2;
    public const STATUS_DONE = 3;
    public const STATUS_ERROR = -1;
    public const STATUS_NOT_FOUND = -2;
    public const STATUS_NOT_CONNECT = -3;

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_PROGRESS,
        self::STATUS_DONE,
        self::STATUS_ERROR,
        self::STATUS_NOT_FOUND,
        self::STATUS_NOT_CONNECT,
    ];

    protected string $table = 'files_queue';

    /**
     * @return FileQueueModel[]
     */
    public static function getDownList(): iterable
    {
        return FileQueueModel::select([
            'loading' => FileQueueModel::LOADING_DOWN,
            'status' => FileQueueModel::STATUS_NEW,
        ], [], 0, 10);
    }

    /**
     * @return FileQueueModel[]
     */
    public static function getUpList(): iterable
    {
        return FileQueueModel::select([
            'loading' => FileQueueModel::LOADING_UP,
            'status' => FileQueueModel::STATUS_NEW,
        ], [], 0, 10);
    }
}