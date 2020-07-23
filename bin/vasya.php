<?php
declare(strict_types = 1);

use HomeCli\Run;

require_once __DIR__ . '/../src/Run.php';

$run = new Run();

$run->preload();

array_shift($argv);

try {
    $run->command($argv[0] ?? 'list', $argv);
} catch (Exception $e) {
    error_log($e->getMessage());
}