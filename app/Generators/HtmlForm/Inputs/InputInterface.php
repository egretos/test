<?php

namespace App\Generators\HtmlForm\Inputs;

interface InputInterface
{
    public function getIdentifiers(): array;

    public function setName(string $name);

    public function setData(array $data);

    public function generate(): string ;
}
