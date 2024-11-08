<?php

namespace Verba\Blueprints\create\templates;


use Verba\Blueprints\create\fxs\AttrFx_SwitchBool;

class AttrTemplate_Active
{
    public function apply($row): array
    {
        $r = [
            'attr_code' => 'active',
            'title_ua'=> 'Активно',
            'title_ru'=> 'Активно',
            'title_en' => 'Active',
            'data_type' => 'logic',
            'form_element' => 'text',
            '_' => [
                'fxs' => [
                    [AttrFx_SwitchBool::class],
                ],
                'migration' => [
                    'options' => ['limit' => 1, 'default' => 1],
                ],
                'index' => true,
            ]
        ];

        return array_replace_recursive($r, $row);
    }
}