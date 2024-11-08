<?php

namespace Verba\Blueprints;

use Verba\Configurable;

/**
 * @property string|null $field_name
 * @property string|null $field_type
 * @property array $options
 * @property array $attr_config
 */
class  MigrationIndex extends Configurable
{
    public string|null $name = null;
    public bool|null $unique = null;
    public array $columns = [];
    public array $options = [];

    public function toMigrationCode(): string
    {
        $r = '';
        $options = [
            'name' => $this->name,
        ];

        $options = array_merge($options, $this->options ?? []);

        $r .= '->addIndex('.var_export($this->columns, true).', '.var_export($options, true).')' . PHP_EOL;

        return $r;
    }
}
