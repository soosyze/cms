<?php

declare(strict_types=1);

namespace SoosyzeCore\BackupManager\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Http\Response;
use Soosyze\Components\Http\Stream;

class BackupController extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathServices = dirname(__DIR__) . '/Config/services.php';
        $this->pathRoutes   = dirname(__DIR__) . '/Config/routes.php';
        $this->pathViews    = dirname(__DIR__) . '/Views/';
    }

    public function admin(): ResponseInterface
    {
        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }

        $backups = [];
        if ($isRepository = self::backupmanager()->isRepository()) {
            $backups = self::backupmanager()->listBackups();
        }
        $doBackupRoute = empty($backups)
            ? null
            : self::router()->getRoute('backupmanager.delete.all');

        return self::template()
                ->getTheme('theme_admin')
                ->view('page', [
                    'icon'       => '<i class="fas fa-file-archive"></i>',
                    'title_main' => t('Backups manager')
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'backupmanager/content-backup-admin.php', $this->pathViews, [
                    'backups'          => $backups,
                    'delete_all_route' => $doBackupRoute,
                    'do_backup_route'  => self::router()->getRoute('backupmanager.dobackup'),
                    'is_repository'    => $isRepository,
                    'name_repository'  => self::backupmanager()->getRepository(),
                    'max_backups'      => self::config()->get('settings.max_backups')
        ]);
    }

    public function deleteAll(): ResponseInterface
    {
        $_SESSION[ 'messages' ] = self::backupmanager()->deleteAll()
            ? [ 'success' => [ t('Backups deleted successfuly') ] ]
            : [ 'errors' => [ t('Backups delete failed') ] ];

        return new Redirect(
            self::router()->getRoute('backupmanager.admin'),
            302
        );
    }

    public function download(string $path): ResponseInterface
    {
        if ($content = self::backupmanager()->getBackup($path)) {
            return new Response(200, new Stream($content), [
                'content-type'        => 'application/zip',
                'content-disposition' => 'attachement; filename="' . $path . '.zip"'
            ]);
        }

        return $this->get404();
    }

    public function restore(string $path): ResponseInterface
    {
        $_SESSION[ 'messages' ] = self::backupmanager()->restore($path)
            ? [ 'success' => [ t('Backup restored successfuly') ] ]
            : [ 'errors' => [ t('Backup restore failed') ] ];

        return new Redirect(
            self::router()->getRoute('backupmanager.admin')
        );
    }

    public function delete(string $path, ServerRequestInterface $req): ResponseInterface
    {
        $_SESSION[ 'messages' ] = self::backupmanager()->delete($path)
            ? [ 'success' => [ t('Backup deleted successfuly') ] ]
            : [ 'errors' => [ t('Backup delete failed') ] ];

        return new Redirect(
            self::router()->getRoute('backupmanager.admin'),
            302
        );
    }

    public function doBackup(): ResponseInterface
    {
        $_SESSION[ 'messages' ] = self::backupmanager()->doBackup()
            ? [ 'success' => [ t('Backup done successfuly') ] ]
            : [ 'errors' => [ t('Backup failed') ] ];

        return new Redirect(
            self::router()->getRoute('backupmanager.admin'),
            302
        );
    }
}
