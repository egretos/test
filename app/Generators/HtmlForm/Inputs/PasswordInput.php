<?php

namespace App\Generators\HtmlForm\Inputs;

class PasswordInput extends BaseInput
{
    public function getIdentifiers(): array
    {
        return [
            'password',
        ];
    }

    public function generate(): string
    {
        return "<fakePasswordInput name={$this->name}}>";
    }
}
