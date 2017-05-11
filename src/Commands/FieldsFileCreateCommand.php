<?php

namespace CrestApps\CodeGenerator\Commands;

use Route;
use Exception;
use Illuminate\Console\Command;
use CrestApps\CodeGenerator\Traits\CommonCommand;
use CrestApps\CodeGenerator\Support\Helpers;
use CrestApps\CodeGenerator\Support\Config;
use CrestApps\CodeGenerator\Support\FieldTransformer;

class FieldsFileCreateCommand extends Command
{
    use CommonCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fields-file:create
                            {file-name : The name of the file to create or write fields too.}
                            {--names= : A comma seperate field names.}
                            {--data-types= : A comma seperated data-type for each field.}
                            {--html-types= : A comma seperated html-type for each field.}
                            {--without-primary-key : The directory where the controller is under.}
                            {--force : Override existing file if one exists.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new fields file.';

    /**
     * Executes the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $input = $this->getCommandInput();
        $file = $this->getFilename($input->file);

        if ($this->isFileExists($file) && ! $input->force) {
            $this->error('The fields-file already exists! To override the existing file, use --force option to append.');

            return false;
        }

        if(empty($input->names)) {
            $this->error('No names were provided. Please use the --names option to pass field names.');

            return false;
        }

        $fields = $this->getFields($input);
        $string = $this->getFieldAsJson($fields, JSON_PRETTY_PRINT);

        $this->createFile($file, $string)
             ->info('New fields-file was crafted!');
    }

    /**
     * Gets a clean user inputs.
     *
     * @return object
     */
    protected function getCommandInput()
    {
        $file = trim($this->argument('file-name'));
        $names = array_unique(Helpers::convertStringToArray(trim($this->option('names'))));
        $dataTypes = Helpers::convertStringToArray(trim($this->option('data-types')));
        $htmlTypes = Helpers::convertStringToArray(trim($this->option('html-types')));
        $withoutPrimaryKey = $this->option('without-primary-key');
        $force = $this->option('force');

        return (object) compact('file', 'names', 'dataTypes', 'htmlTypes', 'withoutPrimaryKey', 'force');
    }

    /**
     * Gets the destenation filename.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getFilename($name)
    {
        $path = base_path(Config::getFieldsFilePath());
        $name = Helpers::postFixWith($name, '.json');

        return $path . $name;
    }

    /**
     * Get primary key properties.
     *
     * @param object $input
     *
     * @return string
     */
    protected function getFields($input)
    {
        $fields = [];

        if(!$input->withoutPrimaryKey) {
            $fields[] = $this->getPrimaryKey();
        }

        foreach ($input->names as $key => $name) {
            $properties = ['name' => $name];

            if ( isset($input->htmlTypes[$key])) {
                $properties['html-type'] = $input->htmlTypes[$key];
            }

            if ( isset($input->dataTypes[$key])) {
                $properties['data-type'] = $input->dataTypes[$key];
            }

            $fields[] = $properties;
        }

        return FieldTransformer::json(json_encode($fields), 'generic');
    }

    /**
     * Converts field's object into a user friendly json string.
     *
     *
     * @return string
     */
    protected function getFieldAsJson($fields)
    {
        $rarField =  array_map(function ($field) {
            return $field->toArray();
        }, $fields);

        return json_encode($rarField, JSON_PRETTY_PRINT);
    }

    /**
     * Get standard properties for primary key field.
     *
     * @return array
     */
    protected function getPrimaryKey()
    {
        return [
            'name' => 'id',
            'type' => 'Integer',
            'is-primary' => true,
            'is-auto-increment' => true
        ];
    }
}
