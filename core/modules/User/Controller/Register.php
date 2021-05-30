<?php

declare(strict_types=1);

namespace SoosyzeCore\User\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Http\Redirect;
use Soosyze\Components\Util\Util;
use Soosyze\Components\Validator\Validator;
use SoosyzeCore\User\Form\FormUser;

class Register extends \Soosyze\Controller
{
    public function __construct()
    {
        $this->pathViews = dirname(__DIR__) . '/Views/';
    }

    public function create(): ResponseInterface
    {
        $values = [];

        if (isset($_SESSION[ 'inputs' ])) {
            $values = $_SESSION[ 'inputs' ];
            unset($_SESSION[ 'inputs' ]);
        }

        $form = (new FormUser([
            'action' => self::router()->getRoute('user.register.store'),
            'method' => 'post'
            ], null, self::config()))
            ->setValues($values);

        $form->group('login-fieldset', 'fieldset', function ($formbuilder) use ($form) {
            $formbuilder->legend('register-legend', t('User registration'));
            $form->usernameGroup($formbuilder)
                ->emailGroup($formbuilder)
                ->passwordNewGroup($formbuilder)
                ->passwordConfirmGroup($formbuilder)
                ->passwordPolicy($formbuilder)
                ->eulaGroup($formbuilder, self::router());
        })->submitForm(t('Registration'));

        $messages = [];
        if (isset($_SESSION[ 'messages' ])) {
            $messages = $_SESSION[ 'messages' ];
            unset($_SESSION[ 'messages' ]);
        }
        if (isset($_SESSION[ 'errors_keys' ])) {
            $form->addAttrs($_SESSION[ 'errors_keys' ], [ 'class' => 'is-invalid' ]);
            unset($_SESSION[ 'errors_keys' ]);
        }

        if (($connectUrl = self::config()->get('settings.connect_url', ''))) {
            $connectUrl = '/' . $connectUrl;
        }

        return self::template()
                ->view('page', [
                    'icon'       => '<i class="fa fa-user" aria-hidden="true"></i>',
                    'title_main' => t('Registration')
                ])
                ->view('page.messages', $messages)
                ->make('page.content', 'user/content-register-create.php', $this->pathViews, [
                    'form'        => $form,
                    'url_relogin' => self::router()->getRoute('user.login', [
                        ':url' => $connectUrl
                    ])
        ]);
    }

    public function store(ServerRequestInterface $req): ResponseInterface
    {
        $route     = self::router()->getRoute('user.register.create');
        $validator = (new Validator())->setInputs($req->getParsedBody());

        $isEmail = ($user = self::user()->getUser($validator->getInput('email')))
            ? $user[ 'email' ]
            : '';

        $isUsername = ($user = self::user()->getUserByUsername($validator->getInput('username')))
            ? $user[ 'username' ]
            : '';

        $validator
            ->addInput('is_email', $isEmail)
            ->addInput('is_username', $isUsername)
            ->addInput('is_rgpd', self::config()->get('settings.rgpd_show', ''))
            ->addInput('is_terms_of_service', self::config()->get('settings.terms_of_service_show', ''))
            ->setRules([
                'username'         => 'required|string|max:255|!equal:@is_username',
                'email'            => 'required|string|email|!equal:@is_email',
                'password_new'     => 'required|string|regex:' . self::user()->passwordPolicy(),
                'password_confirm' => 'required|string|equal:@password_new',
                'rgpd'             => 'required_with:is_rgpd',
                'terms_of_service' => 'required_with:is_terms_of_service',
                'token_user_form'  => 'required|token'
            ])
            ->setLabels([
                'username'         => t('User name'),
                'email'            => t('E-mail'),
                'password_new'     => t('New Password'),
                'password_confirm' => t('Confirmation of the new password'),
                'rgpd'             => t('Accepter la politique de confidentialité'),
                'terms_of_service' => t('Accepter les conditions générale d\'utilisation')
            ])
            ->setMessages([
                'password_confirm' => [
                    'equal' => [
                        'must' => ':label is incorrect'
                    ]
                ]
        ]);

        $this->container->callHook('register.store.validator', [ &$validator ]);

        if ($validator->isValid()) {
            $data = [
                'username'         => $validator->getInput('username'),
                'email'            => $validator->getInput('email'),
                'password'         => self::auth()->hash($validator->getInput('password_new')),
                'token_actived'    => Util::strRandom(30),
                'time_installed'   => (string) time(),
                'timezone'         => 'Europe/Paris',
                'terms_of_service' => (bool) $validator->hasInput('terms_of_service'),
                'rgpd'             => (bool) $validator->hasInput('rgpd'),
            ];

            $this->container->callHook('register.store.before', [ $validator, &$data ]);
            self::query()
                ->insertInto('user', array_keys($data))
                ->values($data)
                ->execute();

            $user = self::user()->getUserActived($data[ 'email' ], false);

            self::query()
                ->insertInto('user_role', [ 'user_id', 'role_id' ])
                ->values([ $user[ 'user_id' ], 2 ])
                ->execute();

            $this->sendMailRegister($data[ 'email' ]);
            $this->container->callHook('register.store.after', [ $validator ]);

            return new Redirect($route);
        }
        $_SESSION[ 'inputs' ]               = $validator->getInputs();
        $_SESSION[ 'messages' ][ 'errors' ] = $validator->getKeyErrors();
        $_SESSION[ 'errors_keys' ]          = $validator->getKeyInputErrors();

        return new Redirect($route);
    }

    public function activate(int $id, string $token, ServerRequestInterface $req): ResponseInterface
    {
        if (!($user = self::user()->find($id)) && $user[ 'token_actived' ] !== $token) {
            return $this->get404($req);
        }

        $this->container->callHook('register.activate.before', [ $id ]);
        self::query()
            ->update('user', [ 'token_actived' => null, 'actived' => true ])
            ->where('user_id', '==', $id)
            ->execute();
        $this->container->callHook('register.activate.after', [ $id ]);

        $_SESSION[ 'messages' ][ 'success' ] = [
            t('Your user account has just been activated, you can now login.')
        ];

        return new Redirect(self::router()->getRoute('user.login', [
            ':url' => ''
        ]));
    }

    private function sendMailRegister(string $from): void
    {
        $user     = self::user()->getUser($from);
        $urlReset = self::router()->getRoute('user.activate', [
            ':id'    => $user[ 'user_id' ],
            ':token' => $user[ 'token_actived' ]
        ]);

        $message = t('A user registration request has been made.') . "<br><br>\n";
        $message .= t('You can now validate the creation of your user account by clicking on this link or by copying it to your browser: ') . "\n";
        $message .= '<a target="_blank" href="' . $urlReset . '" rel="noopener noreferrer" data-auth="NotApplicable">' . $urlReset . "</a><br>\n";
        $message .= t('This link can only be used once.');

        $mail = self::mailer()
            ->from(self::config()->get('mailer.email'))
            ->to($from)
            ->subject(t('User registration'))
            ->message($message)
            ->isHtml(true);

        if ($mail->send()) {
            $_SESSION[ 'messages' ][ 'success' ][] = t('An email with instructions to access your account has just been sent to you. Warning ! This can be in your junk mail.');
        } else {
            $_SESSION[ 'messages' ][ 'errors' ][] = t('An error prevented your email from being sent.');
        }
    }
}
