<?php
declare(strict_types = 1);

namespace HomeCli;

class Config
{
    protected const PATH_CONFIG_JSON = '/configs/configs.json';

    protected array $configs;

    private static self $self;

    protected function __construct()
    {
        $this->configs = require __DIR__ . '/configs/app.php';

        if (file_exists(__DIR__ . self::PATH_CONFIG_JSON)) {
            $json = json_decode(file_get_contents(__DIR__ . self::PATH_CONFIG_JSON), true);

            $this->configs = array_merge($this->configs, $json);
        }
    }

    public static function create(): self
    {
        if (empty(self::$self)) {
            self::$self = new self();
        }

        return self::$self;
    }

    /**
     * @param string|null $path
     * @param mixed|null $default
     * @return array|mixed|null
     */
    public function get(string $path = null, $default = null)
    {
        $config = $this->configs;

        if ($path) {
            foreach (explode('.', $path) as $key) {
                if (empty($config[$key])) {
                    return $default;
                }

                $config = $config[$key];
            }
        }

        return $config;
    }

    /**
     * @param string $path
     * @param mixed $value
     */
    public function set(string $path, $value): void
    {
        $config =& $this->configs;

        foreach (explode('.', $path) as $key) {
            if (empty($config[$key])) {
                $config[$key] = null;
            }
            $config =& $config[$key];
        }

        $config = $value;
    }

    public function save(): void
    {
        file_put_contents(__DIR__ . self::PATH_CONFIG_JSON, json_encode($this->configs));
    }
}