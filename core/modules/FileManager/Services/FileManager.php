<?php

namespace SoosyzeCore\FileManager\Services;

use Soosyze\App;
use Soosyze\Components\Router\Router;
use Soosyze\Components\Util\Util;
use Soosyze\Config;
use SoosyzeCore\FileManager\Hook\Config as HookConfig;
use SoosyzeCore\FileManager\Hook\User as HookUser;
use SoosyzeCore\Template\Services\Block;
use SoosyzeCore\Template\Services\Templating;

class FileManager
{
    /**
     * @var array
     */
    private static $extAllowed = [
        '7z',
        'ai', 'avi',
        'css', 'csv',
        'doc', 'docx',
        'eps',
        'file',
        'gif', 'gzip',
        'html',
        'ico',
        'jpeg', 'jpg', 'json',
        'mp3', 'mp4', 'mpeg',
        'odp', 'ods', 'odt',
        'pdf', 'png', 'ppt', 'pptx',
        'rar',
        'svg',
        'tar', 'txt',
        'xhtml', 'xls', 'xlsx', 'xml',
        'zip'
    ];

    /**
     * @var string
     */
    private $copyFileLink;

    /**
     * @var App
     */
    private $core;

    /**
     * @var HookUser
     */
    private $hookUser;

    /**
     * @var string
     */
    private $pathViews;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Templating
     */
    private $templating;

    public function __construct(App $core, Config $config, HookUser $hookUser, Router $router, Templating $templating)
    {
        $this->core       = $core;
        $this->hookUser   = $hookUser;
        $this->router     = $router;
        $this->templating = $templating;

        $this->copyFileLink = $config->get('settings.copy_link_file', 1) === HookConfig::COPY_ABSOLUTE
            ? $this->core->getPath('files_public', 'public/files')
            : '/' . $this->core->getSettingEnv('files_public', 'public/files');

        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public static function getExtAllowed(): array
    {
        return self::$extAllowed;
    }

    public function getBreadcrumb(string $path, string $keyRoute = 'filemanager.show'): array
    {
        $path       = rtrim($path, '/');
        $nextPath   = '';
        $breadcrumb = [];

        foreach (explode('/', $path) as $key => $value) {
            $nextPath .= "/$value";
            if (!$this->hookUser->hookFolderShow($nextPath)) {
                continue;
            }

            $breadcrumb[ $key ] = [
                'title_link' => empty($value)
                ? '<i class="fa fa-home" aria-hidden="true"></i> ' . t('Home')
                : $value,
                'link'       => $this->router->getRoute($keyRoute, [
                    ':path' => Util::cleanPath($nextPath)
                ]),
                'active'     => ''
            ];
        }
        if (isset($breadcrumb[ $key ])) {
            $breadcrumb[ $key ][ 'active' ] = 'active';
        }

        return $breadcrumb;
    }

    public function parseDir(
        \SplFileInfo $dir,
        string $path,
        string $name = '',
        string $keyRoute = 'filemanager.show'
    ): array {
        $info = self::parseRecursive($dir->getPathname());

        return [
            'actions'    => $this->getActionsFolder($path, $name),
            'ext'        => 'dir',
            'link_show'  => $this->router->getRoute($keyRoute, [
                ':path' => Util::cleanPath("$path/" . $dir->getBasename())
            ]),
            'name'       => $dir->getBasename(),
            'path'       => $dir->getPath(),
            'size'       => Util::strFileSizeFormatted($info[ 'size' ], 2, t('Empty folder')),
            'size_octet' => $info[ 'size' ],
            'time'       => strftime('%d/%m/%Y %H:%M', $info[ 'time' ]
                ? $info[ 'time' ]
                : $dir->getMTime()),
            'type'       => 'dir'
        ];
    }

    public function parseFile(\SplFileInfo $file, string $path): array
    {
        $path = Util::cleanPath($path);

        $name = $file->getBasename('.' . $file->getExtension());
        $ext  = $file->getExtension();

        return [
            'actions'    => $this->getActionsFile($file, $path),
            'ext'        => $ext,
            'link_show'  => $this->router->getRoute('filemanager.file.show', [
                ':path' => $path, ':name' => '/' . $name, ':ext'  => '.' . $ext
            ]),
            'name'       => $name,
            'path'       => $path,
            'size'       => Util::strFileSizeFormatted($file->getSize()),
            'size_octet' => $file->getSize(),
            'time'       => strftime('%d/%m/%Y %H:%M', $file->getMTime()),
            'type'       => in_array($ext, [ 'gif', 'ico', 'jpg', 'jpeg', 'png' ])
                ? 'image'
                : 'file',
            'link'       => $this->core->getPath('files_public') . $path . '/' . $file->getFilename()
        ];
    }

    public function getActionsFolder(string $path, string $name = ''): array
    {
        $actions = [];
        if ($this->hookUser->hookFolderUpdate("$path$name")) {
            $actions[] = [
                'class'      => 'mod',
                'icon'       => 'fa fa-edit',
                'key'        => 'filemanager.folder.edit',
                'link'       => $this->router->getRoute('filemanager.folder.edit', [
                    ':path' => "$path$name"
                ]),
                'title_link' => t('Rename')
            ];
        }
        if ($this->hookUser->hookFolderDelete("$path$name")) {
            $actions[] = [
                'class'      => 'mod',
                'icon'       => 'fa fa-times',
                'key'        => 'filemanager.folder.remove',
                'link'       => $this->router->getRoute('filemanager.folder.remove', [
                    ':path' => "$path$name"
                ]),
                'title_link' => t('Delete')
            ];
        }

        return $actions;
    }

    public function getFileSubmenu(string $keyRoute, \SplFileInfo $file, string $path): Block
    {
        $menu = $this->getActionsFile($file, $path);

        $this->core->callHook('filemanager.file.submenu', [ &$menu ]);

        return $this->templating
                ->getTheme('theme_admin')
                ->createBlock('filemanager/modal-submenu.php', $this->pathViews)
                ->addVars([
                    'key_route' => $keyRoute,
                    'menu'      => $menu
        ]);
    }

    public function getFolderSubmenu(string $keyRoute, string $path): Block
    {
        $menu = $this->getActionsFolder($path);

        $this->core->callHook('filemanager.folder.submenu', [ &$menu ]);

        return $this->templating
                ->getTheme('theme_admin')
                ->createBlock('filemanager/modal-submenu.php', $this->pathViews)
                ->addVars([
                    'key_route' => $keyRoute,
                    'menu'      => $menu
        ]);
    }

    public function getActionsFile(\SplFileInfo $file, string $path): array
    {
        $actions = [];
        $name    = '/' . $file->getBasename('.' . $file->getExtension());
        $ext     = $file->getExtension();

        if ($this->hookUser->hookFileShow($path, $name, $ext)) {
            $actions[] = [
                'class'      => 'mod',
                'icon'       => 'far fa-eye',
                'key'        => 'filemanager.file.show',
                'link'       => $this->router->getRoute('filemanager.file.show', [
                    ':path' => $path, ':name' => $name, ':ext'  => '.' . $ext
                ]),
                'title_link' => 'View',
                'type'       => 'button'
            ];
        }
        if ($this->hookUser->hookFileUpdate($path, $name, $ext)) {
            $actions[] = [
                'class'      => 'mod',
                'icon'       => 'fa fa-edit',
                'key'        => 'filemanager.file.edit',
                'link'       => $this->router->getRoute('filemanager.file.edit', [
                    ':path' => $path, ':name' => $name, ':ext'  => '.' . $ext
                ]),
                'title_link' => 'Rename',
                'type'       => 'button'
            ];
        }
        if ($this->hookUser->hookFileDelete($path, $name, $ext)) {
            $actions[] = [
                'class'      => 'mod',
                'icon'       => 'fa fa-times',
                'key'        => 'filemanager.file.remove',
                'link'       => $this->router->getRoute('filemanager.file.remove', [
                    ':path' => $path, ':name' => $name, ':ext'  => '.' . $ext
                ]),
                'title_link' => 'Delete',
                'type'       => 'button'
            ];
        }
        if ($this->hookUser->hookFileDownlod($path, $name, $ext)) {
            $actions[] = [
                'class'      => '',
                'icon'       => 'fa fa-download',
                'key'        => 'filemanager.file.download',
                'link'       => $this->router->getRoute('filemanager.file.download', [
                    ':path' => $path, ':name' => $name, ':ext'  => '.' . $ext
                ]),
                'title_link' => 'Download',
                'type'       => 'link'
            ];
        }
        if ($this->hookUser->hookFileCopyClipboard($path, $name, $ext)) {
            $actions[] = [
                'class'      => 'copy-clipboard',
                'icon'       => 'fa fa-copy',
                'key'        => '',
                'link'       => $this->copyFileLink . $path . '/' . $file->getFilename(),
                'title_link' => 'Copy link',
                'type'       => 'button'
            ];
        }
        if ($this->hookUser->hookFileCopy($path, $name, $ext)) {
            $actions[] = [
                'class'      => 'mod',
                'icon'       => 'fa fa-copy',
                'key'        => 'filemanager.copy.admin',
                'link'       => $this->router->getRoute('filemanager.copy.admin', [
                    ':path' => $path, ':name' => $name, ':ext'  => '.' . $ext
                ]),
                'title_link' => 'Deplace or copy',
                'type'       => 'button'
            ];
        }

        return $actions;
    }

    public static function parseRecursive(string $dir): array
    {
        $dirIterator = new \RecursiveDirectoryIterator($dir);
        $iterator    = new \RecursiveIteratorIterator($dirIterator);

        $size = 0;
        $time = 0;

        $iterator->rewind();
        foreach ($iterator as $file) {
            if ($iterator->isDot() || $iterator->isLink()) {
                continue;
            }
            /* Fichier instance de SplFileInfo */
            $size += $file->getSize();
            if ($file->getMTime() > $time) {
                $time = $file->getMTime();
            }
        }

        return [ 'size' => $size, 'time' => $time ];
    }
}
