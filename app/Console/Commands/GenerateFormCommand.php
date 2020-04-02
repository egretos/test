<?php

namespace App\Console\Commands;

use App\Generators\HtmlForm\HtmlForm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GenerateFormCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'form:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $json = Storage::get('public/formData.json');
        $formData = json_decode($json, true);

        $validator = Validator::make($formData, [
            'type' => [
                'required',
                'string',
                'in:form',
            ],
            'action' => [
                'required',
                'string',
            ],
            'fields' => [
                'array',
                'min:1',
            ],
            'fields.*.type' => [
                'required',
                'string'
            ],
            'fields.*.name' => [
                'required',
                'string'
            ],
        ]);

        if ($validator->fails()) {
            $this->alert($validator->errors()->first());

            return 0;
        }

        $form = new HtmlForm();
        $form->action = $formData['action'];

        foreach ($formData['fields'] as $field) {
            $form->pushJsonInput($field);
        }

        dd($form->generate());

        echo 1;

        return 1;
    }
}
