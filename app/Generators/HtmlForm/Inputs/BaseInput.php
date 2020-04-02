<?php

namespace App\Generators\HtmlForm\Inputs;

abstract class BaseInput implements InputInterface
{
    protected $name;

    protected $data;

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }
}
