<?php

declare(strict_types=1);

namespace SoosyzeCore\Config;

use Psr\Http\Message\ServerRequestInterface;
use Soosyze\Components\Form\FormBuilder;
use Soosyze\Components\Validator\Validator;

interface ConfigInterface
{
    public function defaultValues(): array;

    public function menu(array &$menu): void;

    public function form(FormBuilder &$form, array $data, ServerRequestInterface $req): void;

    public function validator(Validator &$validator): void;

    public function files(array &$inputsFile): void;

    public function before(Validator &$validator, array &$data, string $id): void;

    public function after(Validator &$validator, array $data, string $id): void;
}
