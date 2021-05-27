<?php

declare(strict_types=1);

namespace SoosyzeCore\FileManager\Hook;

use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Util\Util;
use SoosyzeCore\FileManager\Services\FileProfil;
use SoosyzeCore\User\Services\User as UserService;

class User implements \SoosyzeCore\User\UserInterface
{
    const OCTET_IN_MEGAOCTET = 1048576;

    /**
     * @var FileProfil
     */
    private $fileProfil;

    /**
     * @var UserService
     */
    private $user;

    public function __construct(FileProfil $fileProfil, UserService $user)
    {
        $this->fileProfil = $fileProfil;
        $this->user       = $user;
    }

    public function hookUserPermissionModule(array &$permissions): void
    {
        $permissions[ 'FileManager' ] = [
            'filemanager.permission.admin' => 'Administer file permissions'
        ];
    }

    public function getRight(string $path, ?int $userId = null): array
    {
        if (empty($userId) && ($user = $this->user->isConnected())) {
            $userId = $user[ 'user_id' ];
        }

        $path    = $path === '/' || $path === ''
            ? '/'
            : Util::cleanPath('/' . $path);
        $profils = $this->fileProfil->getProfilsFileByUser($userId);

        foreach ($profils as $profil) {
            $pattern = $profil[ 'folder_show' ];
            $pattern = str_replace(':user_id', $userId, $pattern);
            $pattern = preg_quote($pattern, '/');
            $pattern .= $profil[ 'folder_show_sub' ]
                ? '.*'
                : '';

            if (preg_match('/^' . $pattern . '$/', $path)) {
                return $profil;
            }
        }

        return [];
    }

    public function hookFileAdmin(): string
    {
        return 'filemanager.permission.admin';
    }

    public function hookFileShow(
        string $path,
        string $name,
        string $ext,
        ?ServerRequestInterface $req = null,
        ?array $user = null
    ): bool {
        $right = $this->getRight($path, $user[ 'user_id' ] ?? null);

        if (empty($right)) {
            return false;
        }

        return $this->rightExtension($ext, $right);
    }

    public function hookFileStore(string $path, ?ServerRequestInterface $req = null, ?array $user = null): bool
    {
        $right = $this->getRight($path, $user[ 'user_id' ] ?? null);

        return !empty($right[ 'file_store' ]);
    }

    public function hookFileUpdate(
        string $path,
        string $name,
        string $ext,
        ?ServerRequestInterface $req = null,
        ?array $user = null
    ): bool {
        $right = $this->getRight($path, $user[ 'user_id' ] ?? null);

        if (empty($right[ 'file_update' ])) {
            return false;
        }

        return $this->rightExtension($ext, $right);
    }

    public function hookFileCopyClipboard(
        string $path,
        string $name,
        string $ext,
        ?ServerRequestInterface $req = null,
        ?array $user = null
    ): bool {
        $right = $this->getRight($path, $user[ 'user_id' ] ?? null);

        return !empty($right[ 'file_clipboard' ]);
    }

    public function hookFileCopy(
        string $path,
        string $name,
        string $ext,
        ?ServerRequestInterface $req = null,
        ?array $user = null
    ): bool {
        $right = $this->getRight($path, $user[ 'user_id' ] ?? null);

        return !empty($right[ 'file_copy' ]);
    }

    public function hookFileDelete(
        string $path,
        string $name,
        string $ext,
        ?ServerRequestInterface $req = null,
        ?array $user = null
    ): bool {
        $right = $this->getRight($path, $user[ 'user_id' ] ?? null);

        if (empty($right[ 'file_delete' ])) {
            return false;
        }

        return $this->rightExtension($ext, $right);
    }

    public function hookFileDownlod(
        string $path,
        string $name,
        string $ext,
        ?ServerRequestInterface $req = null,
        ?array $user = null
    ): bool {
        $right = $this->getRight($path, $user[ 'user_id' ] ?? null);

        if (empty($right[ 'file_download' ])) {
            return false;
        }

        return $this->rightExtension($ext, $right);
    }

    public function hookFolderAdmin(? ServerRequestInterface $req = null, ?array $user = null): bool
    {
        $profils = $this->fileProfil->getProfilsFileByUser($user[ 'user_id' ]);

        return !empty($profils);
    }

    public function hookFolderShow(string $path, ?ServerRequestInterface $req = null, ?array $user = null): bool
    {
        $right = $this->getRight($path, $user[ 'user_id' ] ?? null);

        return !empty($right);
    }

    public function hookFolderStore(string $path, ?ServerRequestInterface $req = null, ?array $user = null): bool
    {
        $right = $this->getRight($path, $user[ 'user_id' ] ?? null);

        return !empty($right[ 'folder_store' ]) && !empty($right[ 'folder_show_sub' ]);
    }

    public function hookFolderUpdate(string $path, ?ServerRequestInterface $req = null, ?array $user = null): bool
    {
        $right = $this->getRight($path, $user[ 'user_id' ] ?? null);

        return !empty($right[ 'folder_update' ]) && !empty($right[ 'folder_show_sub' ]);
    }

    public function hookFolderDelete(string $path, ?ServerRequestInterface $req = null, ?array $user = null): bool
    {
        $right = $this->getRight($path, $user[ 'user_id' ] ?? null);

        return !empty($right[ 'folder_delete' ]) && !empty($right[ 'folder_show_sub' ]);
    }

    public function getMaxUpload(string $path): int
    {
        $profil = $this->getRight($path);

        if (empty($profil[ 'file_size' ])) {
            return Util::getOctetUploadLimit();
        }

        $maxUpload = $profil[ 'file_size' ] * self::OCTET_IN_MEGAOCTET;

        return min(Util::getOctetUploadLimit(), $maxUpload);
    }

    private function rightExtension(string $ext, array $right = []): bool
    {
        if ($right[ 'file_extensions_all' ]) {
            return true;
        }

        return in_array(trim($ext, '.'), explode(',', $right[ 'file_extensions' ]));
    }
}
