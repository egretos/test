<?php

namespace App\Generators\HtmlForm\Inputs;

class EmailInput extends BaseInput
{
    public function getIdentifiers(): array
    {
        return [
            'email',
        ];
    }

    public function generate(): string
    {
        return "<fakeEmailInput name={$this->name}}>";
    }
}
