<?php

declare(strict_types=1);

namespace SoosyzeCore\FileManager\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Http\Response;
use Soosyze\Components\Http\Stream;
use Soosyze\Components\Util\Util;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\FileManager\Hook\Config;
use SoosyzeCore\FileManager\Services\FileManager;
use SoosyzeCore\Template\Services\Block;

class File extends \Soosyze\Controller
{
    /**
     * @var array
     */
    private static $extensionImage = [
        'gif', 'ico', 'jpg', 'jpeg', 'png'
    ];

    /**
     * @var array
     */
    private static $extensionCode = [
        'css', 'csv', 'html', 'json', 'txt', 'xhtml', 'xml'
    ];

    /**
     * @var array
     */
    private static $extensionVideo = [
        'mp4', 'mpeg'
    ];

    /**
     * @var array
     */
    private static $extensionAudio = [
        'mp3'
    ];

    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    /**
     * @return Block|ResponseInterface
     */
    public function show(string $path, string $name, string $ext, ServerRequestInterface $req)
    {
        if (!$req->isAjax()) {
            return $this->get404($req);
        }

        $spl = new \SplFileInfo(
            self::core()->getDir('files_public', 'app/files') . "$path$name$ext"
        );
        if (!$spl->isFile()) {
            return $this->get404($req);
        }
        $data = self::filemanager()->parseFile($spl, $path);

        $visualize = $this->visualizeFile($data, self::core()->getPath('files_public', 'app/files') . "$path$name$ext");

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('filemanager/modal-file-show.php', $this->pathViews)
                ->addBlock('visualize', $visualize[ 'block' ])
                ->addVars([
                    'file'  => $spl,
                    'info'  => $data,
                    'menu'  => self::filemanager()->getFileSubmenu('filemanager.file.show', $spl, $path),
                    'title' => t('See the file'),
                    'type'  => $visualize[ 'type' ]
        ]);
    }

    public function create(string $path): Block
    {
        $path = Util::cleanPath($path);
        /** @var \SoosyzeCore\FileManager\Hook\User $hookUser */
        $hookUser = $this->get('filemanager.hook.user');
        $max      = $hookUser->getMaxUpload($path);

        $form = (new FormBuilder([
                'action'  => self::router()->getRoute('filemanager.file.store', [
                    ':path' => $path
                ]),
                'class'   => 'filemanager-dropfile',
                'method'  => 'post',
                'onclick' => 'document.getElementById(\'files\').click();',
                ]))
            ->group('file-group', 'div', function ($form) use ($max) {
                $form->label(
                    'file-label',
                    '<div class="filemanager-dropfile__label">'
                    . '<i class="fa fa-download" aria-hidden="true"></i> '
                    . '<span class="choose">' . t('Choose files') . '</span> '
                    . t('or drag them here.')
                    . '<p><small>'
                    . t('Files size is limited to :max per upload', [
                        ':max' => Util::strFileSizeFormatted($max)
                    ])
                    . '</small></p>'
                    . '</div>'
                )
                ->file('file[]', [
                    'id'       => 'files',
                    'multiple' => 1,
                    'style'    => 'display:none'
                ]);
            });

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('filemanager/modal-form.php', $this->pathViews)
                ->addVars([
                    'is_progress' => 1,
                    'form'        => $form,
                    'title'       => t('Add a new file')
        ]);
    }

    public function store(string $path, ServerRequestInterface $req): ResponseInterface
    {
        if (!$req->isAjax()) {
            return $this->get404($req);
        }
        if ($req->isMaxSize()) {
            return $this->json(400, [
                'messages' => [
                    'type'   => t('Error'),
                    'errors' => [
                        t('The total amount of data received exceeds the maximum value allowed by the post_max_size directive in your php.ini file.')
                    ]
                ]
            ]);
        }

        $dir    = self::core()->getDir('files_public', 'app/files') . $path;
        /** @var \SoosyzeCore\FileManager\Hook\User $hookUser */
        $hookUser = $this->get('filemanager.hook.user');
        $profil   = $hookUser->getRight($path);

        $rules  = [
            'file'   => 'required|file_extensions:',
            'folder' => '!required',
        ];

        $rules[ 'file' ] .= !empty($profil[ 'file_extensions_all' ])
            ? implode(',', FileManager::getExtAllowed())
            : $profil[ 'file_extensions' ];

        if (!empty($profil[ 'file_size' ])) {
            $rules[ 'file' ] .= '|max:' . $profil[ 'file_size' ] . 'mb';
        }
        if (!empty($profil[ 'folder_size' ])) {
            $rules[ 'folder' ] = 'max:' . $profil[ 'folder_size' ] . 'mb';
        }

        $validator = (new Validator())
            ->setRules($rules)
            ->addLabel('file', t('File'))
            ->setMessages([
                'file'   => [
                    'max' => [
                        'must' => t('File size is limited to :max per upload')
                    ]
                ],
                'folder' => [
                    'max' => [
                        'must' => t('You exceed your quota of :max of data authorized in this directory')
                    ]
                ]
            ])
            ->setInputs($req->getParsedBody() + $req->getUploadedFiles());

        $validator->addInput('folder', 0);
        if (is_dir($dir)) {
            $sizeFile   = $validator->getInput('file', 0)
                ? $validator->getInput('file')->getSize()
                : 0;
            $sizefolder = self::filemanager()->parseRecursive($dir)[ 'size' ];

            $validator->addInput('folder', $sizefolder + $sizeFile);
        }

        if (!$validator->isValid()) {
            return $this->json(400, [
                'messages' => [
                    'type'   => t('Error'),
                    'errors' => $validator->getKeyErrors()
                ]
            ]);
        }

        $file = $validator->getInput('file');

        $filename = Util::strSlug(pathinfo($file->getClientFilename(), PATHINFO_FILENAME));
        $ext      = Util::getFileExtension($file->getClientFilename());

        $serviceFile = self::file()
            ->add($file)
            ->setPath($path)
            ->isResolvePath();

        if (self::config()->get('settings.replace_file') === Config::KEEP_RENAME) {
            $serviceFile = $serviceFile->isResolveName();
        } elseif (self::config()->get('settings.replace_file') === Config::KEEP_REFUSE && is_file("$dir/$filename.$ext")) {
            return $this->json(400, [
                'messages' => [
                    'type'   => t('Error'),
                    'errors' => [ t('An existing file has the same name, you can not replace it') ]
                ]
            ]);
        }

        $serviceFile->saveOne();

        return $this->json(200, [
            'ext'       => $ext,
            'link_file' => $serviceFile->getMovePathAbsolute(),
            'messages'  => [
                'type'    => t('Success'),
                'success' => [ t('The file has been uploaded') ]
            ],
            'name'      => $serviceFile->getName(),
            'type'      => in_array($ext, self::$extensionImage)
                ? 'image'
                : 'file'
        ]);
    }

    /**
     * @return Block|ResponseInterface
     */
    public function edit(string $path, string $name, string $ext, ServerRequestInterface $req)
    {
        if (!$req->isAjax()) {
            return $this->get404($req);
        }

        $spl = new \SplFileInfo(
            self::core()->getDir('files_public', 'app/files') . "$path$name$ext"
        );
        if (!$spl->isFile()) {
            return $this->get404($req);
        }
        $data = self::filemanager()->parseFile($spl, $path);

        if (isset($_SESSION[ 'inputs' ])) {
            $data = array_merge($data, $_SESSION[ 'inputs' ]);
            unset($_SESSION[ 'inputs' ]);
        }

        $action = self::router()->getRoute('filemanager.file.update', [
            ':path' => $path, ':name' => $name, ':ext'  => $ext
        ]);

        $form = (new FormBuilder([ 'action' => $action, 'method' => 'post']))
            ->group('file-fieldset', 'fieldset', function ($form) use ($data) {
                $form->legend('file-legend', t('Rename the file'))
                ->group('name-group', 'div', function ($form) use ($data) {
                    $form->label('name-label', t('Name'), [
                        'data-tooltip' => t('All non-alphanumeric characters or hyphens will be replaced by an underscore (_) or their unaccented equivalent.')
                    ])
                    ->text('name', [
                        'class'     => 'form-control',
                        'maxlenght' => 255,
                        'required'  => 1,
                        'value'     => $data[ 'name' ]
                    ]);
                }, [ 'class' => 'form-group' ]);
            })
            ->token('token_file_update')
            ->submit('submit', t('Save'), [ 'class' => 'btn btn-success' ]);

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('filemanager/modal-form.php', $this->pathViews)
                ->addVars([
                    'form'  => $form,
                    'info'  => $data,
                    'menu'  => self::filemanager()->getFileSubmenu('filemanager.file.edit', $spl, $path),
                    'title' => t('Rename the file'),
        ]);
    }

    public function update(string $path, string $name, string $ext, ServerRequestInterface $req): ResponseInterface
    {
        if (!$req->isAjax()) {
            return $this->get404($req);
        }

        $dir         = self::core()->getDir('files_public', 'app/files') . $path;
        $fileCurrent = "$dir$name$ext";

        $validator = (new Validator())
            ->setRules([
                'dir'               => 'required|dir',
                'file_current'      => 'required|is_file',
                'name'              => 'required|string|max:255',
                'token_file_update' => 'token'
            ])
            ->addLabel('name', t('Name'))
            ->setInputs($req->getParsedBody())
            ->addInput('dir', $dir)
            ->addInput('file_current', $fileCurrent);

        $out = [];
        /* Si les valeur attendues sont les bonnes. */
        if (!$validator->isValid()) {
            $out[ 'errors_keys' ]          = $validator->getKeyInputErrors();
            $out[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();

            return $this->json(400, $out);
        }

        $nameUpdate = Util::strSlug($validator->getInput('name'));
        $fileUpdate = "$dir/$nameUpdate$ext";

        /* Si le nouveau nom du fichier est déjà utilisé. */
        if ($fileCurrent === $fileUpdate || !is_file($fileUpdate)) {
            rename($fileCurrent, $fileUpdate);

            $out[ 'messages' ][ 'success' ] = [ t('The file has been renamed') ];

            return $this->json(200, $out);
        }

        $out[ 'errors_keys' ]          = $validator->getKeyInputErrors();
        $out[ 'messages' ][ 'errors' ] = [ t('You can not use this name to rename the file') ];

        return $this->json(400, $out);
    }

    /**
     * @return Block|ResponseInterface
     */
    public function remove(string $path, string $name, string $ext, ServerRequestInterface $req)
    {
        if (!$req->isAjax()) {
            return $this->get404($req);
        }

        $spl = new \SplFileInfo(
            self::core()->getDir('files_public', 'app/files') . "$path$name$ext"
        );
        if (!$spl->isFile()) {
            return $this->get404($req);
        }

        $action = self::router()->getRoute('filemanager.file.delete', [
            ':path' => $path, ':name' => $name, ':ext'  => $ext
        ]);

        $form = (new FormBuilder([ 'action' => $action, 'method' => 'post' ]))
            ->group('file-fieldset', 'fieldset', function ($form) use ($name, $ext) {
                $form->legend('file-legend', t('Delete file'))
                ->group('info-group', 'div', function ($form) use ($name, $ext) {
                    $form->html('info', '<p:attr>:content</p>', [
                        ':content' => t('Warning ! The deletion of the @name file is final.', [
                            '@name' => "$name$ext"
                        ])
                    ]);
                }, [ 'class' => 'alert alert-warning' ]);
            })
            ->token('token_file_delete')
            ->submit('submit', t('Delete'), [ 'class' => 'btn btn-danger' ]);

        return self::template()
                ->getTheme('theme_admin')
                ->createBlock('filemanager/modal-form.php', $this->pathViews)
                ->addVars([
                    'form'  => $form,
                    'info'  => self::filemanager()->parseFile($spl, $path),
                    'menu'  => self::filemanager()->getFileSubmenu('filemanager.file.remove', $spl, $path),
                    'title' => t('Delete file')
        ]);
    }

    public function delete(string $path, string $name, string $ext, ServerRequestInterface $req): ResponseInterface
    {
        if (!$req->isAjax()) {
            return $this->get404($req);
        }

        $dir  = self::core()->getDir('files_public', 'app/files') . $path;
        $file = "$dir$name$ext";

        $validator = (new Validator())
            ->setRules([
                'dir'               => 'required|dir',
                'file'              => 'required|is_file',
                'token_file_delete' => 'token'
            ])
            ->setInputs($req->getParsedBody())
            ->addInput('dir', $dir)
            ->addInput('file', $file);

        $out = [];
        if ($validator->isValid()) {
            unlink($file);
            $out[ 'messages' ][ 'success' ] = [ t('The file has been deleted') ];

            return $this->json(200, $out);
        }

        $out[ 'errors_keys' ]          = $validator->getKeyInputErrors();
        $out[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();

        return $this->json(400, $out);
    }

    public function download(string $path, string $name, string $ext, ServerRequestInterface $req): ResponseInterface
    {
        $file = self::core()->getDir('files_public', 'app/files') . "$path$name$ext";
        if (!is_file($file)) {
            return $this->get404($req);
        }

        $stream = new Stream(fopen($file, 'r+'));

        return (new Response(200, $stream))
                ->withHeader('content-type', 'application/octet-stream')
                ->withHeader('content-length', $stream->getSize())
                ->withHeader('content-disposition', 'attachment; filename=' . substr("$name$ext", 1))
                ->withHeader('pragma', 'no-cache')
                ->withHeader('cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
                ->withHeader('expires', '0');
    }

    private function visualizeFile(array $info, string $path): array
    {
        self::template()->getTheme('theme_admin');

        if (in_array($info[ 'ext' ], self::$extensionImage)) {
            return [
                'block' => self::template()
                    ->createBlock('filemanager/modal-file-show_visualize-image.php', $this->pathViews)
                    ->addVar('path', $path),
                'type'  => 'img'
            ];
        }
        if (in_array($info[ 'ext' ], self::$extensionCode)) {
            $code = file_get_contents($path);

            return [
                'block' => self::template()
                    ->createBlock('filemanager/modal-file-show_visualize-code.php', $this->pathViews)
                    ->addVars([
                        'code'      => htmlspecialchars($code),
                        'extension' => $info[ 'ext' ]
                    ]),
                'type'  => 'code'
            ];
        }
        if (in_array($info[ 'ext' ], self::$extensionVideo)) {
            return [
                'block' => self::template()
                    ->createBlock('filemanager/modal-file-show_visualize-video.php', $this->pathViews)
                    ->addVars([
                        'path'      => $path,
                        'extension' => $info[ 'ext' ]
                    ]),
                'type'  => 'video'
            ];
        }
        if (in_array($info[ 'ext' ], self::$extensionAudio)) {
            return [
                'block' => self::template()
                    ->createBlock('filemanager/modal-file-show_visualize-audio.php', $this->pathViews)
                    ->addVars([
                        'path'      => $path,
                        'extension' => $info[ 'ext' ]
                    ]),
                'type'  => 'audio'
            ];
        }
        if ($out = $this->container->callHook('filemanager.visualize', [ $info[ 'ext' ] ])) {
            return $out;
        }

        return [
            'block' => self::template()
                ->createBlock('filemanager/modal-file-show_visualize-default.php', $this->pathViews)
                ->addVar('extension', $info[ 'ext' ]),
            'type'  => 'default'
        ];
    }
}
