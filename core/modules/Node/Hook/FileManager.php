<?php

declare(strict_types=1);

namespace SoosyzeCore\Node\Hook;

use Core;
use Psr\Http\Message\RequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Router\Router;
use SoosyzeCore\System\Services\Modules;

class FileManager
{
    /**
     * @var Core
     */
    private $core;

    /**
     * @var array
     */
    private $hasFileManager;

    /**
     * @var Router
     */
    private $router;

    public function __construct(Core $core, Modules $module, Router $router)
    {
        $this->core   = $core;
        $this->router = $router;

        $this->hasFileManager = $module->has('FileManager');
    }

    public function hookNodeCreateForm(FormBuilder &$form, array $content, string $type): void
    {
        if (!$this->hasFileManager) {
            return;
        }

        $form->append('fields-fieldset', function ($form) {
            $form->group('files-group', 'div', function ($form) {
                $response = '<div class="col-md-12">'
                    . '<div class="alert alert-info">'
                    . t('NOTE: You cannot add media files until you save the content. Just click Save')
                    . '</div>'
                    . '</div>';

                $form->legend('files-manager-label', t('Media'))
                    ->html('files-manager', '<div:attr>:content</div>', [
                        ':content' => $response,
                        'class'    => 'row',
                        'id'       => 'filemanager'
                ]);
            });
        });
    }

    public function hookNodeEditForm(FormBuilder &$form, array $content): void
    {
        if (!$this->hasFileManager) {
            return;
        }

        $request = $this->router->getRequestByRoute('filemanager.show', [
            ':path' => "/node/{$content[ 'type' ]}/{$content[ 'id' ]}"
        ]);
        $this->getFileManager($form, $request);
    }

    public function hookEntityForm(FormBuilder &$form, array $content, array $node, string $entity): void
    {
        if (!$this->hasFileManager) {
            return;
        }

        $request = $this->router->getRequestByRoute('filemanager.show', [
            ':path' => "/node/{$node[ 'type' ]}/{$node[ 'id' ]}/$entity"
        ]);
        $this->getFileManager($form, $request);
    }

    private function getFileManager(FormBuilder &$form, RequestInterface $request): void
    {
        $response = '<div class="col-md-12">'
            . '<div class="alert alert-info">'
            . t('You do not have the necessary permissions to use the file manager')
            . '</div>'
            . '</div>';

        if ($this->core->callHook('app.granted.request', [ $request ])) {
            /** @var array @route */
            $route    = $this->router->parse($request);
            $response = $this->router->execute($route, $request);
        }

        $form->append('fields-fieldset', function ($form) use ($response) {
            $form->group('files-group', 'div', function ($form) use ($response) {
                $form->legend('files-manager-label', t('Media'))
                    ->html('files-manager', '<div:attr>:content</div>', [
                    ':content' => $response,
                    'class'    => 'row filemanager'
                ]);
            });
        });
    }
}
