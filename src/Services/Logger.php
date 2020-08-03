<?php

namespace RB\Cli\Services;

use Exception;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class Logger extends AbstractLogger implements LoggerInterface
{
    protected string $path = __DIR__ . '/../../logs/';
    protected string $name;

    /**
     * Logger constructor.
     * @param string|null $name
     */
    public function __construct(string $name = null)
    {
        $this->name = $name ?? 'log';
        $this->name .= '.log';
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @throws Exception
     */
    public function log($level, $message, array $context = array())
    {
        if (!file_exists($this->path . $this->name) && !mkdir($this->path)) {
            throw new Exception('Невозможно создать дирикторию для логов');
        }

        // TODO if string else toString()

        $fp = fopen($this->path . $this->name, "w");
        fwrite($fp, sprintf(
            '%s %s %s',
            (new \DateTime())->format('Y-m-d H:i:s'),
            $level,
            $message
        ));
        fclose($fp);
    }
}