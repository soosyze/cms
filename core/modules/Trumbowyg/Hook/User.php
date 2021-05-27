<?php

declare(strict_types=1);

namespace SoosyzeCore\Trumbowyg\Hook;

class User implements \SoosyzeCore\User\UserInterface
{
    public function hookUserPermissionModule(array &$permissions): void
    {
        $permissions[ 'Trumbowyg' ] = [
            'trumbowyg.upload' => 'Use image upload'
        ];
    }

    public function hookUpload(): string
    {
        return 'trumbowyg.upload';
    }
}
