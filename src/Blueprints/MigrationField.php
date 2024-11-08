<?php

namespace Verba\Blueprints;

use Verba\Configurable;

/**
 * @property string|null $field_name
 * @property string|null $field_type
 * @property array $options
 * @property array $attr_config
 */
class  MigrationField extends Configurable
{
    public string|null $field_name = null;
    public string|null $field_type = null;

    public array $options = [];
    public array $attr_config = [];

    public function toMigrationCode(): string
    {
        $r = '';

        if(!empty($this->options)) {
            $options = ', '.var_export($this->options, true);
        } else {
            $options = '';
        }

        $r .= '->addColumn(\''.$this->field_name.'\', \''.$this->field_type.'\''.$options.')' . PHP_EOL;


        if(!empty($migration['index'])) {
            $ioptions = [
                'name' => '_idx_'.$migration['field_name'],
            ];

            if(!empty($migration['index']['fields'])){
                $fields = $migration['index']['fields'];
                unset($migration['index']['fields']);
            }else{
                $fields = [$migration['field_name']];
            }

            $ioptions = array_merge($ioptions, $migration['index'] ?? []);


            $r .= '->addIndex('.var_export($fields, true).', '.var_export($ioptions, true).')' . PHP_EOL;
        }

        return $r;
    }
}
