<?php

declare(strict_types=1);

namespace SoosyzeCore\Dashboard\Services;

use Core;
use Soosyze\Config;

class Dashboard
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Core
     */
    private $core;

    public function __construct(Config $config, Core $core)
    {
        $this->config = $config;
        $this->core   = $core;
    }

    public function getSizeDatabase(): int
    {
        $host = $this->config->get('database.host');

        return $this->getSizeDirectoryInterator(ROOT . $host);
    }

    public function getSizeFiles(): int
    {
        $dir = $this->core->getDir('files_public');

        return $this->getSizeDirectoryInterator($dir);
    }

    public function getSizeBackups(): int
    {
        $dir = $this->core->getDir('backup_dir');

        return $this->getSizeDirectoryInterator($dir);
    }

    public function getSizeDirectoryInterator(string $dir): int
    {
        $size = 0;
        if (is_dir($dir)) {
            $dirIterator = new \RecursiveDirectoryIterator($dir);
            $iterator    = new \RecursiveIteratorIterator($dirIterator);

            $iterator->rewind();
            foreach ($iterator as $file) {
                if ($iterator->isDot() || $iterator->isLink()) {
                    continue;
                }
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
