<?php

declare(strict_types=1);

namespace SoosyzeCore\FileManager\Services;

use SoosyzeCore\FileManager\Hook\User;

class FilterManagerIterator extends \FilterIterator
{
    /**
     * @var User
     */
    private $hookUser;

    /**
     * @var string
     */
    private $path;

    public function __construct(User $hookUser)
    {
        $this->hookUser = $hookUser;
    }

    public function load(string $path, \Iterator $iterator): self
    {
        parent::__construct($iterator);
        $this->path = $path;

        return $this;
    }

    public function accept(): bool
    {
        $file   = $this->current();
        $accept = true;
        if ($file->isDot() || $file->isLink()) {
            $accept = false;
        } elseif ($file->isFile()) {
            $name = '/' . $file->getBasename('.' . $file->getExtension());
            $ext  = $file->getExtension();

            if (!in_array($ext, FileManager::getExtAllowed())) {
                $accept = false;
            } elseif ($file->getBasename() === '.' . $file->getExtension()) {
                $accept = false;
            } elseif (!$this->hookUser->hookFileShow($this->path, $name, $ext)) {
                $accept = false;
            }
        } elseif ($file->isDir() && !$this->hookUser->hookFolderShow($this->path . '/' . $file->getBasename())) {
            $accept = false;
        }

        return $accept;
    }
}
