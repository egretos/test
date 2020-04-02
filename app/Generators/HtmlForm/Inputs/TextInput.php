<?php

namespace App\Generators\HtmlForm\Inputs;

class TextInput extends BaseInput
{
    public function getIdentifiers(): array
    {
        return [
            'text',
        ];
    }

    public function generate(): string
    {
        return "<fakeTextInput name={$this->name}}>";
    }
}
