<?php

declare(strict_types=1);

namespace SoosyzeCore\Block\Hook;

use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Validator\Validator;

final class Config implements \SoosyzeCore\Config\ConfigInterface
{
    private $socials = [
        'blogger'    => 'Blogger',
        'codepen'    => 'Codepen',
        'discord'    => 'Discord',
        'dribbble'   => 'Dribbble',
        'facebook'   => 'Facebook',
        'github'     => 'GitHub',
        'gitlab'     => 'GitLab',
        'instagram'  => 'Instagram',
        'linkedin'   => 'Linkedin',
        'mastodon'   => 'Mastodon',
        'snapchat'   => 'Snapchat',
        'soundcloud' => 'Soundcloud',
        'spotify'    => 'Spotify',
        'steam'      => 'Steam',
        'tumblr'     => 'Tumblr',
        'twitch'     => 'Twitch',
        'twitter'    => 'Twitter',
        'youtube'    => 'Youtube'
    ];

    public function defaultValues(): array
    {
        return [ 'icon_socials' => array_fill_keys(array_keys($this->socials), '') ];
    }

    public function menu(array &$menu): void
    {
        $menu[ 'social' ] = [
            'title_link' => 'Social networks'
        ];
    }

    public function form(FormBuilder &$form, array $data, ServerRequestInterface $req): void
    {
        $form->group('social-fieldset', 'fieldset', function ($form) use ($data) {
            $form->legend('social-legend', t('Social networks'));
            foreach ($this->socials as $key => $social) {
                $form->group("$key-group", 'div', function ($form) use ($data, $key, $social) {
                    $form->label("$key-label", $social, [ 'for' => $key ])
                        ->group("$key-flex", 'div', function ($form) use ($data, $key) {
                            $form->html("$key-icon", '<span:attr>:content</span>', [
                                ':content' => '<i class="fab fa-' . $key . '" aria-hidden="true"></i>'
                            ])
                            ->text($key, [
                                'class' => 'form-control',
                                'value' => $data['icon_socials'][ $key ]
                            ]);
                        }, [ 'class' => 'form-group-flex' ]);
                }, [ 'class' => 'form-group' ]);
            }
        });
    }

    public function validator(Validator &$validator): void
    {
        foreach (array_keys($this->socials) as $key) {
            $validator->addRule($key, '!required|route_or_url');
        }
        $validator->setLabels($this->socials);
    }

    public function before(&$validator, array &$data, string $id): void
    {
        $data[ 'icon_socials' ] = [];
        foreach (array_keys($this->socials) as $key) {
            if ($validator->getInput($key)) {
                $data[ 'icon_socials' ][ $key ] = $validator->getInput($key);
            }
        }
    }

    public function after(Validator &$validator, array $data, string $id): void
    {
    }

    public function files(array &$inputsFile): void
    {
    }
}
