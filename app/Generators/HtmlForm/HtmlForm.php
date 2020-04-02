<?php

namespace App\Generators\HtmlForm;

use App\Generators\HtmlForm\Inputs\InputInterface;

class HtmlForm
{
    public $action;

    private $inputClasses = [];

    /** @var array|InputInterface[] */
    private $inputs = [];

    /**
     * HtmlForm constructor.
     * @throws
     */
    public function __construct()
    {
        foreach (glob(app()->basePath().'/app/Generators/HtmlForm/Inputs/*Input.php') as $file)
        {
            require_once $file;

            // get the file name of the current file without the extension
            // which is essentially the class name
            $class = 'App\Generators\HtmlForm\Inputs\\'.basename($file, '.php');

            $class = new \ReflectionClass($class);

            if ($class->implementsInterface(InputInterface::class) && !$class->isAbstract())
            {
                $this->pushInputClasses($class->newInstance());
            }
        }
    }

    public function pushInput(InputInterface $input) {
        $this->inputs[] = $input;
    }

    public function pushJsonInput(array $jsonData) {
        if (key_exists($jsonData['type'], $this->inputClasses)) {
            /** @var InputInterface $input */
            $input = new $this->inputClasses[ $jsonData['type'] ];

            $input->setName($jsonData['name']);
            $input->setData($jsonData);

            $this->pushInput($input);
        }
    }

    public function pushInputClasses(InputInterface $input) {
        foreach ($input->getIdentifiers() as $identifier) {
            $this->inputClasses[$identifier] = get_class($input);
        }
    }

    public function generate() {
        $html = "<fakehtml>";

        foreach ($this->inputs as $input) {
            $html .= $input->generate();
        }

        $html .= "</fakehtml>";

        return $html;
    }
}
