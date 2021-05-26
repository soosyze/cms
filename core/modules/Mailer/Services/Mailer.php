<?php

declare(strict_types=1);

namespace SoosyzeCore\Mailer\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    /**
     * @var PHPMailer
     */
    private $mailer;

    /**
     * @var array
     */
    private $values = [
        'driver'          => 'mail',
        'smtp_encryption' => '',
        'smtp_host'       => '',
        'smtp_password'   => '',
        'smtp_port'       => '',
        'smtp_username'   => ''
    ];

    public function __construct(array $config = [])
    {
        $this->values = array_merge($this->values, $config);

        $this->mailer = new PHPMailer;
        $this->values[ 'driver' ] === 'mail'
                ? $this->mailer->isMail()
                : $this->mailer->isSMTP();
    }

    public function isSMTP(): void
    {
        $this->mailer->isSMTP();

        $this->mailer->Host       = $this->values[ 'smtp_host' ];
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $this->values[ 'smtp_username' ];
        $this->mailer->Password   = $this->values[ 'smtp_password' ];
        $this->mailer->SMTPSecure = $this->values[ 'smtp_encryption' ] === 'tls'
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;
        $this->mailer->Port       = $this->values[ 'smtp_port' ];
    }

    public function to(string $address, string $name = ''): self
    {
        $this->mailer->addAddress($address, $name);

        return $this;
    }

    public function addAttachment(string $attachement): self
    {
        $this->mailer->addAttachment($attachement);

        return $this;
    }

    public function addCc(string $address, string $name = ''): self
    {
        $this->mailer->addCC($address, $name);

        return $this;
    }

    public function addBcc(string $address, string $name = ''): self
    {
        $this->mailer->addBCC($address, $name);

        return $this;
    }

    public function from(string $address, string $name = ''): self
    {
        $this->mailer->setFrom($address, $name);

        return $this;
    }

    public function replayTo(string $address, string $name = ''): self
    {
        $this->mailer->addReplyTo($address, $name);

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->mailer->Subject = $subject;

        return $this;
    }

    public function message(string $body): self
    {
        $this->mailer->Body = $body;

        return $this;
    }

    public function isHtml(bool $isHtml = true): self
    {
        $this->mailer->isHTML($isHtml);

        return $this;
    }

    public function send(): bool
    {
        try {
            return $this->mailer->send();
        } catch (Exception $e) {
            return (bool) $this->mailer->ErrorInfo;
        }
    }
}
