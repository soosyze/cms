<?php

declare(strict_types=1);

namespace SoosyzeCore\Block\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Http\Response;
use Soosyze\Components\Validator\Validator;

class Section extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function admin(string $theme, ServerRequestInterface $req): ResponseInterface
    {
        $vendor = self::core()->getPath('modules', 'modules/core', false) . '/Block/Assets';

        return self::template()
                ->getTheme(
                    $theme === 'admin'
                    ? 'theme_admin'
                    : 'theme'
                )
                ->addStyle('block', "$vendor/css/block.css")
                ->addScript('block', "$vendor/js/block.js")
                ->view('page', [
                    'icon'       => '<i class="fa fa-columns" aria-hidden="true"></i>',
                    'title_main' => t('Editing blocks')
                ])
                ->make('page.content', 'block/content-section-admin.php', $this->pathViews, [
                    'content'          => $theme === 'admin'
                        ? 'Edit public theme blocks'
                        : 'Edit admin theme blocks',
                    'link_theme_index' => self::router()->getRoute('system.theme.index'),
                    'link_section'     => self::router()->getRoute('block.section.admin', [
                        ':theme' => $theme === 'admin'
                        ? 'public'
                        : 'admin'
                    ])
        ]);
    }

    public function update(int $id, ServerRequestInterface $req): ResponseInterface
    {
        if (!self::query()->from('block')->where('block_id', '==', $id)->fetch()) {
            return $this->get404($req);
        }

        $validator = (new Validator())
            ->setRules([
                'weight'  => 'required|string|max:50',
                'section' => 'required|string|max:50'
            ])
            ->setInputs($req->getParsedBody());

        if ($validator->isValid()) {
            self::query()
                ->update('block', [
                    'weight'  => (int) $validator->getInput('weight'),
                    'section' => $validator->getInput('section')
                ])
                ->where('block_id', '==', $id)
                ->execute();
        }

        return new Response(200);
    }
}
