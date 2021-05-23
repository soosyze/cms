<?php

namespace SoosyzeCore\System\Services;

class Migration
{
    const REGEX_MIGRATION_NAME = '/^2[\d]{3}_(0[1-9]|1[0-2])_(0[1-9]|[12][\d]|3[01])_\d{6}_[a-z0-9_]+/';

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var \Soosyze\Config
     */
    private $config;

    /**
     * @var \SoosyzeCore\QueryBuilder\Services\Query
     */
    private $query;

    /**
     * @var \SoosyzeCore\QueryBuilder\Services\Schema
     */
    private $schema;

    public function __construct($composer, $config, $query, $schema)
    {
        $this->composer = $composer;
        $this->config   = $config;
        $this->query    = $query;
        $this->schema   = $schema;
    }

    /**
     * Si une migration est disponible.
     *
     * @return bool
     */
    public function isMigration()
    {
        $titleModulesActive  = $this->query->from('module_active')->lists('title');
        $migrationsInstalled = $this->query->from('migration')->lists('migration');
        $composers           = $this->composer->getModuleComposers();

        foreach ($titleModulesActive as $title) {
            if (!isset($composers[ $title ])) {
                continue;
            }
            $extendClass = $this->composer->getExtendClass($title, $composers);

            $dir = (new $extendClass)->getDir() . DS . 'Migrations';
            if (!\is_dir($dir)) {
                continue;
            }

            foreach (new \DirectoryIterator($dir) as $fileInfo) {
                if (
                    $fileInfo->isFile() &&
                    !in_array($fileInfo->getBasename('.php'), $migrationsInstalled) &&
                    preg_match(self::REGEX_MIGRATION_NAME, $fileInfo->getBasename('.php'))) {
                    return true;
                }
            }
        }
        $this->config->set('settings.module_update_time', time());

        return false;
    }

    /**
     * Installe les migrations non installé des modules actifs.
     */
    public function migrate()
    {
        $titleModulesActive  = $this->query->from('module_active')->lists('title');
        $migrationsInstalled = $this->query->from('migration')->lists('migration');
        $composers           = $this->composer->getModuleComposers();

        $callbacks = [];
        foreach ($titleModulesActive as $title) {
            if (!isset($composers[ $title ])) {
                continue;
            }
            $extendClass = $this->composer->getExtendClass($title, $composers);

            $dir = (new $extendClass)->getDir() . DS . 'Migrations';
            if (!\is_dir($dir)) {
                continue;
            }

            foreach (new \DirectoryIterator($dir) as $fileInfo) {
                if (
                    !$fileInfo->isFile() ||
                    in_array($fileInfo->getBasename('.php'), $migrationsInstalled) ||
                    !preg_match(self::REGEX_MIGRATION_NAME, $fileInfo->getBasename('.php'))
                    ) {
                    continue;
                }

                $callbacks[ $fileInfo->getBasename('.php') ] = [
                    'callback'  => include_once $fileInfo->getRealPath(),
                    'extension' => $title,
                    'migration' => $fileInfo->getBasename('.php')
                ];
            }
        }

        ksort($callbacks);
        $query = clone $this->query;
        $this->query->insertInto('migration', [ 'migration', 'extension' ]);

        foreach ($callbacks as $callback) {
            try {
                if (isset($callback[ 'callback' ][ 'up' ])) {
                    call_user_func_array(
                        $callback[ 'callback' ][ 'up' ],
                        [ $this->schema, $query ]
                    );
                }
                if (isset($callback[ 'callback' ][ 'up_config' ])) {
                    call_user_func_array(
                        $callback[ 'callback' ][ 'up_config' ],
                        [ $this->config ]
                    );
                }
            } catch (\Exception $e) {
                throw $e;
            }
            $query->init();
            $this->query->values([
                $callback[ 'migration' ], $callback[ 'extension' ]
            ]);
        }

        $this->query->execute();
        $this->config
            ->set('settings.module_update', false)
            ->set('settings.module_update_time', time());
    }

    /**
     * Installe les migrations d'un module.
     *
     * @param string $dir       Chemin des migrations
     * @param string $extension Titre du module
     *
     * @return void
     */
    public function installMigration($dir, $extension)
    {
        if (!\is_dir($dir)) {
            return;
        }

        $this->query->insertInto('migration', [ 'migration', 'extension' ]);

        foreach (new \DirectoryIterator($dir) as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $this->query->values([ $fileInfo->getBasename('.php'), $extension ]);
        }

        $this->query->execute();
    }

    public function uninstallMigration($extension)
    {
        $this->query
            ->delete()
            ->from('migration')
            ->where('extension', '=', $extension)
            ->execute();
    }
}
